<?php

namespace Tests\Feature\Security;

use App\Enums\BreakingNewsStatus;
use App\Enums\BreakingNewsTargetType;
use App\Models\Article;
use App\Models\BreakingNews;
use App\Models\User;
use App\Services\BreakingNewsAdministrationService;
use App\Support\Security\Rbac;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BreakingNewsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_editor_can_create_scheduled_breaking_news_for_article(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $article = Article::factory()->create();
        $startsAt = now()->addMinutes(10);
        $endsAt = now()->addHour();

        $breakingNews = app(BreakingNewsAdministrationService::class)->create($this->userWithRole(Rbac::NEWS_EDITOR), [
            'headline_bn' => 'জরুরি সংবাদ',
            'target_type' => BreakingNewsTargetType::Article->value,
            'article_id' => $article->getKey(),
            'external_url' => 'https://example.com/ignored',
            'priority' => 50,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => BreakingNewsStatus::Active->value,
        ]);

        $this->assertSame($article->getKey(), $breakingNews->article_id);
        $this->assertNull($breakingNews->external_url);
        $this->assertFalse($breakingNews->isCurrentlyActive());
    }

    public function test_active_scope_honors_scheduled_start_expiry_status_and_priority_order(): void
    {
        Carbon::setTestNow('2026-09-11 18:00:00');

        $liveLow = BreakingNews::factory()->create([
            'headline_bn' => 'Live low',
            'status' => BreakingNewsStatus::Active,
            'priority' => 1,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMinute(),
        ]);
        $liveHigh = BreakingNews::factory()->create([
            'headline_bn' => 'Live high',
            'status' => BreakingNewsStatus::Active,
            'priority' => 10,
            'starts_at' => now()->subMinutes(2),
            'ends_at' => now()->addMinutes(2),
        ]);
        BreakingNews::factory()->create([
            'headline_bn' => 'Future',
            'status' => BreakingNewsStatus::Active,
            'starts_at' => now()->addMinute(),
            'ends_at' => now()->addHour(),
        ]);
        BreakingNews::factory()->create([
            'headline_bn' => 'Expired',
            'status' => BreakingNewsStatus::Active,
            'starts_at' => now()->subHour(),
            'ends_at' => now(),
        ]);
        BreakingNews::factory()->create([
            'headline_bn' => 'Inactive',
            'status' => BreakingNewsStatus::Inactive,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);

        $activeIds = BreakingNews::query()->currentlyActive()->pluck('id')->all();

        $this->assertSame([$liveHigh->getKey(), $liveLow->getKey()], $activeIds);

        Carbon::setTestNow();
    }

    public function test_reporter_cannot_manage_breaking_news(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->expectException(AuthorizationException::class);

        app(BreakingNewsAdministrationService::class)->create($this->userWithRole(Rbac::REPORTER), [
            'headline_bn' => 'Denied',
            'target_type' => BreakingNewsTargetType::None->value,
            'priority' => 0,
            'status' => BreakingNewsStatus::Inactive->value,
        ]);
    }

    public function test_external_breaking_news_requires_valid_https_url(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->expectException(ValidationException::class);

        app(BreakingNewsAdministrationService::class)->create($this->userWithRole(Rbac::NEWS_EDITOR), [
            'headline_bn' => 'External',
            'target_type' => BreakingNewsTargetType::External->value,
            'external_url' => 'javascript:alert(1)',
            'priority' => 0,
            'status' => BreakingNewsStatus::Active->value,
        ]);
    }

    public function test_article_target_requires_article_and_external_target_clears_article(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->expectException(ValidationException::class);

        app(BreakingNewsAdministrationService::class)->create($this->userWithRole(Rbac::NEWS_EDITOR), [
            'headline_bn' => 'Missing article',
            'target_type' => BreakingNewsTargetType::Article->value,
            'priority' => 0,
            'status' => BreakingNewsStatus::Active->value,
        ]);
    }

    public function test_reorder_updates_priority_and_rejects_invalid_ids(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $editor = $this->userWithRole(Rbac::NEWS_EDITOR);
        $first = BreakingNews::factory()->create(['priority' => 0]);
        $second = BreakingNews::factory()->create(['priority' => 0]);

        app(BreakingNewsAdministrationService::class)->reorder($editor, [$second->getKey(), $first->getKey()]);

        $this->assertSame(2, $second->refresh()->priority);
        $this->assertSame(1, $first->refresh()->priority);

        $this->expectException(ValidationException::class);

        app(BreakingNewsAdministrationService::class)->reorder($editor, [$first->getKey(), 999999]);
    }

    public function test_headline_strips_html_to_reduce_stored_xss(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $breakingNews = app(BreakingNewsAdministrationService::class)->create($this->userWithRole(Rbac::NEWS_EDITOR), [
            'headline_bn' => '<script>alert(1)</script>নিরাপদ শিরোনাম',
            'target_type' => BreakingNewsTargetType::None->value,
            'priority' => 0,
            'status' => BreakingNewsStatus::Active->value,
        ]);

        $this->assertSame('নিরাপদ শিরোনাম', $breakingNews->headline_bn);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
