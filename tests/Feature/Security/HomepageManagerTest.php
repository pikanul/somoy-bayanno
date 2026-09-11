<?php

namespace Tests\Feature\Security;

use App\Enums\ArticleStatus;
use App\Enums\HomepageSectionKey;
use App\Enums\HomepageSectionStatus;
use App\Models\Article;
use App\Models\HomepageItem;
use App\Models\HomepageSection;
use App\Models\User;
use App\Services\HomepageManagerService;
use App\Support\Security\Rbac;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HomepageManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_editor_can_create_and_publish_homepage_section(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Log::spy();

        $article = Article::factory()->published()->create(['headline_bn' => 'Managed lead']);
        $editor = $this->userWithRole(Rbac::EDITOR_IN_CHIEF);

        $section = app(HomepageManagerService::class)->create($editor, [
            'key' => HomepageSectionKey::MainLead->value,
            'title' => 'Main Lead',
            'status' => HomepageSectionStatus::Draft->value,
            'sort_order' => 0,
            'items' => [
                ['article_id' => $article->getKey()],
            ],
        ]);

        $published = app(HomepageManagerService::class)->publish($editor, $section);

        $this->assertSame(HomepageSectionStatus::Published, $published->status);
        $this->assertNotNull($published->published_at);
        $this->assertSame($editor->getKey(), $published->published_by);
        Log::shouldHaveReceived('info')->with('Homepage manager activity', \Mockery::on(fn (array $context): bool => $context['action'] === 'published'));
    }

    public function test_reporter_cannot_manage_homepage(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->expectException(AuthorizationException::class);

        app(HomepageManagerService::class)->create($this->userWithRole(Rbac::REPORTER), [
            'key' => HomepageSectionKey::TopStories->value,
            'status' => HomepageSectionStatus::Draft->value,
            'sort_order' => 0,
        ]);
    }

    public function test_single_position_rejects_multiple_items_and_duplicate_items(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $first = Article::factory()->published()->create();
        $second = Article::factory()->published()->create();

        $this->expectException(ValidationException::class);

        app(HomepageManagerService::class)->create($this->userWithRole(Rbac::EDITOR_IN_CHIEF), [
            'key' => HomepageSectionKey::MainLead->value,
            'status' => HomepageSectionStatus::Draft->value,
            'sort_order' => 0,
            'items' => [
                ['article_id' => $first->getKey()],
                ['article_id' => $second->getKey()],
            ],
        ]);
    }

    public function test_homepage_manager_rejects_unpublished_articles(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $draft = Article::factory()->create(['status' => ArticleStatus::Draft]);

        $this->expectException(ValidationException::class);

        app(HomepageManagerService::class)->create($this->userWithRole(Rbac::EDITOR_IN_CHIEF), [
            'key' => HomepageSectionKey::TopStories->value,
            'status' => HomepageSectionStatus::Draft->value,
            'sort_order' => 0,
            'items' => [
                ['article_id' => $draft->getKey()],
            ],
        ]);
    }

    public function test_reorder_items_only_accepts_current_section_items(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $editor = $this->userWithRole(Rbac::EDITOR_IN_CHIEF);
        $section = HomepageSection::factory()->create(['key' => HomepageSectionKey::TopStories]);
        $otherSection = HomepageSection::factory()->create(['key' => HomepageSectionKey::EditorsChoice]);
        $first = HomepageItem::factory()->create(['homepage_section_id' => $section->getKey(), 'sort_order' => 0]);
        $second = HomepageItem::factory()->create(['homepage_section_id' => $section->getKey(), 'sort_order' => 1]);
        $other = HomepageItem::factory()->create(['homepage_section_id' => $otherSection->getKey(), 'sort_order' => 0]);

        app(HomepageManagerService::class)->reorderItems($editor, $section, [$second->getKey(), $first->getKey()]);

        $this->assertSame([0, 1], $section->refresh()->items->pluck('sort_order')->all());
        $this->assertSame([$second->getKey(), $first->getKey()], $section->items->pluck('id')->all());

        $this->expectException(ValidationException::class);

        app(HomepageManagerService::class)->reorderItems($editor, $section, [$first->getKey(), $other->getKey()]);
    }

    public function test_public_homepage_uses_published_managed_main_lead_with_safe_fallbacks(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Cache::forget('public-homepage-v1');

        Article::factory()->published()->create([
            'headline_bn' => 'Automatic featured lead',
            'is_featured' => true,
        ]);
        $managedArticle = Article::factory()->published()->create([
            'headline_bn' => 'Managed published lead',
            'is_featured' => false,
        ]);

        $section = HomepageSection::factory()->create([
            'key' => HomepageSectionKey::MainLead,
            'status' => HomepageSectionStatus::Published,
            'published_at' => now(),
        ]);
        HomepageItem::factory()->create([
            'homepage_section_id' => $section->getKey(),
            'article_id' => $managedArticle->getKey(),
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
        ]);

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Managed published lead')
            ->assertSee('Automatic featured lead');
    }

    public function test_expired_managed_item_falls_back_to_automatic_lead(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Cache::forget('public-homepage-v1');

        Article::factory()->published()->create([
            'headline_bn' => 'Automatic fallback lead',
            'is_featured' => true,
        ]);
        $expiredArticle = Article::factory()->create([
            'headline_bn' => 'Expired managed lead',
            'status' => ArticleStatus::Draft,
            'published_at' => null,
        ]);

        $section = HomepageSection::factory()->create([
            'key' => HomepageSectionKey::MainLead,
            'status' => HomepageSectionStatus::Published,
        ]);
        HomepageItem::factory()->create([
            'homepage_section_id' => $section->getKey(),
            'article_id' => $expiredArticle->getKey(),
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->subHour(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Automatic fallback lead')
            ->assertDontSee('Expired managed lead');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
