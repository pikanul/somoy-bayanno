<?php

namespace Tests\Feature\Security;

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Enums\ArticleVisibility;
use App\Models\Author;
use App\Models\Category;
use App\Models\User;
use App\Services\ArticleAdministrationService;
use App\Services\ArticleRevisionService;
use App\Support\Security\Rbac;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ArticleRevisionHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_meaningful_article_edits_create_structured_revisions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $editor = $this->userWithRole(Rbac::EDITOR_IN_CHIEF);
        $firstCategory = Category::factory()->create();
        $secondCategory = Category::factory()->create();
        $firstAuthor = Author::factory()->create();
        $secondAuthor = Author::factory()->create();

        $article = app(ArticleAdministrationService::class)->create($editor, $this->payload([
            'primary_category_id' => $firstCategory->getKey(),
            'category_ids' => [$firstCategory->getKey()],
            'author_ids' => [$firstAuthor->getKey()],
            'headline_bn' => 'পুরোনো শিরোনাম',
            'body_bn' => '<p>পুরোনো প্রতিবেদন</p>',
        ]));

        app(ArticleAdministrationService::class)->update($editor, $article, $this->payload([
            'slug' => $article->slug,
            'primary_category_id' => $secondCategory->getKey(),
            'category_ids' => [$secondCategory->getKey()],
            'author_ids' => [$secondAuthor->getKey()],
            'headline_bn' => 'নতুন শিরোনাম',
            'subheadline_bn' => 'নতুন উপশিরোনাম',
            'summary_bn' => 'নতুন সারাংশ',
            'body_bn' => '<p>নতুন প্রতিবেদন</p>',
            'correction_note' => 'বানান সংশোধন করা হয়েছে',
        ]));

        $revisions = $article->revisions()->reorder()->orderBy('version')->get();

        $this->assertCount(2, $revisions);
        $this->assertSame(1, $revisions[0]->version);
        $this->assertSame(2, $revisions[1]->version);
        $this->assertSame($editor->getKey(), $revisions[1]->changed_by);
        $this->assertSame('নতুন শিরোনাম', $revisions[1]->snapshot['headline_bn']);
        $this->assertSame('নতুন উপশিরোনাম', $revisions[1]->snapshot['subheadline_bn']);
        $this->assertSame('নতুন সারাংশ', $revisions[1]->snapshot['summary_bn']);
        $this->assertSame('<p>নতুন প্রতিবেদন</p>', $revisions[1]->snapshot['body_bn']);
        $this->assertSame([$secondCategory->getKey()], $revisions[1]->snapshot['category_ids']);
        $this->assertSame([$secondAuthor->getKey()], $revisions[1]->snapshot['author_ids']);
        $this->assertSame('বানান সংশোধন করা হয়েছে', $revisions[1]->snapshot['correction_note']);
    }

    public function test_revision_comparison_reports_key_changes(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $editor = $this->userWithRole(Rbac::EDITOR_IN_CHIEF);
        $article = app(ArticleAdministrationService::class)->create($editor, $this->payload([
            'headline_bn' => 'প্রথম শিরোনাম',
            'body_bn' => '<p>প্রথম লেখা</p>',
        ]));

        app(ArticleAdministrationService::class)->update($editor, $article, $this->payload([
            'slug' => $article->slug,
            'headline_bn' => 'দ্বিতীয় শিরোনাম',
            'body_bn' => '<p>দ্বিতীয় লেখা</p>',
        ]));

        $latestRevision = $article->revisions()->orderByDesc('version')->firstOrFail();
        $changes = app(ArticleRevisionService::class)->compare($latestRevision);

        $this->assertSame('প্রথম শিরোনাম', $changes['headline_bn']['from']);
        $this->assertSame('দ্বিতীয় শিরোনাম', $changes['headline_bn']['to']);
        $this->assertSame('<p>প্রথম লেখা</p>', $changes['body_bn']['from']);
        $this->assertSame('<p>দ্বিতীয় লেখা</p>', $changes['body_bn']['to']);
    }

    public function test_authorized_restore_reverts_content_and_creates_new_revision(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $editor = $this->userWithRole(Rbac::EDITOR_IN_CHIEF);
        $firstCategory = Category::factory()->create();
        $secondCategory = Category::factory()->create();

        $article = app(ArticleAdministrationService::class)->create($editor, $this->payload([
            'primary_category_id' => $firstCategory->getKey(),
            'category_ids' => [$firstCategory->getKey()],
            'headline_bn' => 'মূল শিরোনাম',
            'body_bn' => '<p>মূল লেখা</p>',
        ]));
        $originalRevision = $article->revisions()->where('version', 1)->firstOrFail();

        app(ArticleAdministrationService::class)->update($editor, $article, $this->payload([
            'slug' => $article->slug,
            'primary_category_id' => $secondCategory->getKey(),
            'category_ids' => [$secondCategory->getKey()],
            'headline_bn' => 'পরিবর্তিত শিরোনাম',
            'body_bn' => '<p>পরিবর্তিত লেখা</p>',
        ]));

        $restored = app(ArticleRevisionService::class)->restore($editor, $originalRevision, 'Restore verified original copy');

        $this->assertSame('মূল শিরোনাম', $restored->headline_bn);
        $this->assertSame('<p>মূল লেখা</p>', $restored->body_bn);
        $this->assertSame($firstCategory->getKey(), $restored->primary_category_id);
        $this->assertCount(4, $restored->revisions);
        $this->assertSame('Restored revision 1: Restore verified original copy', $restored->revisions()->orderByDesc('version')->first()->change_summary);
    }

    public function test_restore_requires_permission_and_reason(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $editor = $this->userWithRole(Rbac::EDITOR_IN_CHIEF);
        $reporter = $this->userWithRole(Rbac::REPORTER);
        $article = app(ArticleAdministrationService::class)->create($editor, $this->payload());
        $revision = $article->revisions()->firstOrFail();

        $this->expectException(AuthorizationException::class);

        app(ArticleRevisionService::class)->restore($reporter, $revision, 'Trying to restore');
    }

    public function test_restore_requires_non_empty_reason(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $editor = $this->userWithRole(Rbac::EDITOR_IN_CHIEF);
        $article = app(ArticleAdministrationService::class)->create($editor, $this->payload());
        $revision = $article->revisions()->firstOrFail();

        $this->expectException(ValidationException::class);

        app(ArticleRevisionService::class)->restore($editor, $revision, '');
    }

    public function test_revision_history_has_no_public_route(): void
    {
        $this->get('/articles/revisions')
            ->assertNotFound();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /** @param array<string, mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        $category = Category::factory()->create();

        return array_replace([
            'type' => ArticleType::Standard->value,
            'headline_bn' => 'বাংলা সংবাদ শিরোনাম',
            'headline_en' => null,
            'short_headline_bn' => null,
            'slug' => 'revision-news-'.fake()->unique()->numberBetween(1000, 999999),
            'subheadline_bn' => null,
            'summary_bn' => 'সংক্ষিপ্ত সারাংশ',
            'body_bn' => '<p>পূর্ণ সংবাদ প্রতিবেদন</p>',
            'primary_category_id' => $category->getKey(),
            'category_ids' => [],
            'tag_ids' => [],
            'topic_ids' => [],
            'author_ids' => [],
            'reporter_name' => null,
            'location' => null,
            'source_name' => null,
            'source_url' => null,
            'featured_image_url' => null,
            'image_caption' => null,
            'image_credit' => null,
            'video_url' => null,
            'status' => ArticleStatus::Draft->value,
            'visibility' => ArticleVisibility::Public->value,
            'comments_enabled' => true,
            'is_breaking' => false,
            'is_featured' => false,
            'published_at' => null,
            'scheduled_at' => null,
            'updated_content_at' => null,
            'seo_title' => null,
            'seo_description' => null,
            'canonical_url' => null,
            'social_title' => null,
            'social_description' => null,
            'social_image' => null,
            'correction_note' => null,
            'internal_editor_note' => null,
        ], $overrides);
    }
}
