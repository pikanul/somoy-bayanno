<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Enums\ArticleVisibility;
use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ArticleAdministrationService;
use App\Services\ArticleWorkflowService;
use App\Services\CategoryAdministrationService;
use App\Services\UserAdministrationService;
use App\Support\Security\Rbac;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ActivityLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_create_update_and_workflow_actions_are_logged_without_secrets(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $editor = $this->userWithRole(Rbac::EDITOR_IN_CHIEF);

        $article = app(ArticleAdministrationService::class)->create($editor, $this->validArticlePayload([
            'headline_bn' => 'Audit article',
            'password' => 'not accepted or logged',
        ]));

        app(ArticleAdministrationService::class)->update($editor, $article, $this->validArticlePayload([
            'headline_bn' => 'Audit article updated',
            'slug' => $article->slug,
            'primary_category_id' => $article->primary_category_id,
        ]));

        $article->forceFill(['status' => ArticleStatus::Approved])->save();
        app(ArticleWorkflowService::class)->publish($editor, $article->refresh(), 'Ready');
        app(ArticleWorkflowService::class)->unpublish($editor, $article->refresh(), 'Correction');
        app(ArticleWorkflowService::class)->archive($editor, $article->refresh(), 'Expired');

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'article.create',
            'subject_type' => Article::class,
            'subject_id' => $article->getKey(),
            'user_id' => $editor->getKey(),
        ]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'article.update']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'article.publish']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'article.unpublish']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'article.archive']);

        ActivityLog::query()->each(function (ActivityLog $activityLog): void {
            $encoded = json_encode([
                $activityLog->old_values,
                $activityLog->new_values,
                $activityLog->properties,
            ]);

            $this->assertStringNotContainsString('password', $encoded);
            $this->assertStringNotContainsString('not accepted or logged', $encoded);
        });
    }

    public function test_category_changes_user_creation_disable_and_role_changes_are_logged(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->userWithRole(Rbac::SUPER_ADMIN);

        $category = app(CategoryAdministrationService::class)->create($admin, $this->categoryPayload());
        app(CategoryAdministrationService::class)->update($admin, $category, $this->categoryPayload([
            'name_bn' => 'রাজনীতি আপডেট',
            'slug' => $category->slug,
        ]));

        $user = app(UserAdministrationService::class)->create($admin, [
            'name' => 'Managed User',
            'email' => 'managed@example.test',
            'password' => 'Password!234',
            'status' => 'active',
        ], [Rbac::REPORTER]);

        app(UserAdministrationService::class)->update($admin, $user, [
            'name' => 'Managed User',
            'email' => 'managed@example.test',
            'password' => null,
            'status' => 'disabled',
        ], [Rbac::NEWS_EDITOR]);

        $this->assertDatabaseHas('activity_logs', ['action' => 'category.create']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'category.change']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.create']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.disable']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'role.change']);

        $encoded = ActivityLog::query()->pluck('new_values')->implode(' ');

        $this->assertStringNotContainsString('Password!234', $encoded);
    }

    public function test_login_and_failed_login_events_are_logged_with_request_context(): void
    {
        $user = User::factory()->create(['email' => 'login@example.test']);

        $this
            ->withServerVariables([
                'REMOTE_ADDR' => '203.0.113.10',
                'HTTP_USER_AGENT' => 'FeatureTest Browser',
            ])
            ->get('/');

        Event::dispatch(new Login('web', $user, false));
        Event::dispatch(new Failed('web', null, [
            'email' => 'login@example.test',
            'password' => 'wrong-password',
        ]));

        $login = ActivityLog::query()->where('action', 'login')->firstOrFail();
        $failedLogin = ActivityLog::query()->where('action', 'failed_login')->firstOrFail();

        $this->assertSame($user->getKey(), $login->user_id);
        $this->assertSame('203.0.113.10', $login->ip_address);
        $this->assertSame('FeatureTest Browser', $login->user_agent);
        $this->assertSame(['email' => 'login@example.test'], $failedLogin->properties);
    }

    public function test_activity_logger_sanitizes_backup_settings_and_advertisement_payloads(): void
    {
        $user = User::factory()->create();
        $logger = app(ActivityLogger::class);

        $logger->backupAction($user, 'created', [
            'filename' => 'backup.sql',
            'database_password' => 'secret-db-password',
        ]);
        $logger->settingsChange($user, [
            'site_name' => 'Old',
            'api_secret' => 'old-secret',
        ], [
            'site_name' => 'New',
            'api_secret' => 'new-secret',
        ]);
        $logger->advertisementChange($user, null, 'update', newValues: [
            'placement' => 'homepage-top',
            'token' => 'ad-token',
        ]);

        $encoded = ActivityLog::query()->pluck('properties')
            ->merge(ActivityLog::query()->pluck('old_values'))
            ->merge(ActivityLog::query()->pluck('new_values'))
            ->implode(' ');

        $this->assertStringContainsString('backup.sql', $encoded);
        $this->assertStringContainsString('homepage-top', $encoded);
        $this->assertStringNotContainsString('secret-db-password', $encoded);
        $this->assertStringNotContainsString('old-secret', $encoded);
        $this->assertStringNotContainsString('new-secret', $encoded);
        $this->assertStringNotContainsString('ad-token', $encoded);
    }

    public function test_activity_logs_are_read_only_and_auditor_can_view_them(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $auditor = $this->userWithRole(Rbac::AUDITOR);
        $reporter = $this->userWithRole(Rbac::REPORTER);
        $activityLog = ActivityLog::factory()->create();

        $this->actingAs($auditor);
        $this->assertTrue(ActivityLogResource::canViewAny());
        $this->assertFalse(ActivityLogResource::canCreate());
        $this->assertFalse(ActivityLogResource::canEdit($activityLog));
        $this->assertFalse(ActivityLogResource::canDelete($activityLog));

        $this->actingAs($reporter);
        $this->assertFalse(ActivityLogResource::canViewAny());
        $this->assertFalse(ActivityLogResource::canCreate());
        $this->assertFalse(ActivityLogResource::canEdit($activityLog));
        $this->assertFalse(ActivityLogResource::canDelete($activityLog));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /** @param array<string, mixed> $overrides */
    private function validArticlePayload(array $overrides = []): array
    {
        $category = Category::factory()->create();

        return array_replace([
            'type' => ArticleType::Standard->value,
            'headline_bn' => 'বাংলা সংবাদ শিরোনাম',
            'headline_en' => null,
            'short_headline_bn' => null,
            'slug' => 'bangla-news-'.fake()->unique()->numberBetween(1000, 999999),
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

    /** @param array<string, mixed> $overrides */
    private function categoryPayload(array $overrides = []): array
    {
        return array_replace([
            'name_bn' => 'রাজনীতি',
            'name_en' => 'Politics',
            'slug' => 'politics-'.fake()->unique()->numberBetween(1000, 999999),
            'description' => null,
            'parent_id' => null,
            'status' => 'active',
            'sort_order' => 1,
            'show_in_menu' => true,
            'seo_title' => null,
            'seo_description' => null,
        ], $overrides);
    }
}
