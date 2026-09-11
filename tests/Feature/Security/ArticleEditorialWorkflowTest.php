<?php

namespace Tests\Feature\Security;

use App\Enums\ArticleStatus;
use App\Enums\ArticleWorkflowAction;
use App\Models\Article;
use App\Models\User;
use App\Services\ArticleWorkflowService;
use App\Support\Security\Rbac;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ArticleEditorialWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_reporter_can_submit_own_draft_for_review(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $reporter = $this->userWithRole(Rbac::REPORTER);
        $article = Article::factory()->create([
            'created_by' => $reporter->getKey(),
            'status' => ArticleStatus::Draft,
        ]);

        $updated = app(ArticleWorkflowService::class)->submit($reporter, $article, 'Ready for review');

        $this->assertSame(ArticleStatus::PendingReview, $updated->status);
        $this->assertWorkflowEvent($updated, ArticleWorkflowAction::Submit, ArticleStatus::Draft, ArticleStatus::PendingReview, $reporter, 'Ready for review');
    }

    public function test_reporter_cannot_publish_draft_directly(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $reporter = $this->userWithRole(Rbac::REPORTER);
        $article = Article::factory()->create([
            'created_by' => $reporter->getKey(),
            'status' => ArticleStatus::Draft,
        ]);

        $this->expectException(AuthorizationException::class);

        app(ArticleWorkflowService::class)->publish($reporter, $article);
    }

    public function test_news_editor_can_approve_pending_review_article(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $editor = $this->userWithRole(Rbac::NEWS_EDITOR);
        $article = Article::factory()->create(['status' => ArticleStatus::PendingReview]);

        $updated = app(ArticleWorkflowService::class)->approve($editor, $article, 'Approved');

        $this->assertSame(ArticleStatus::Approved, $updated->status);
        $this->assertWorkflowEvent($updated, ArticleWorkflowAction::Approve, ArticleStatus::PendingReview, ArticleStatus::Approved, $editor, 'Approved');
    }

    public function test_editor_can_return_pending_review_article_with_reason(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $editor = $this->userWithRole(Rbac::NEWS_EDITOR);
        $article = Article::factory()->create(['status' => ArticleStatus::PendingReview]);

        $updated = app(ArticleWorkflowService::class)->returnForRevision($editor, $article, 'Needs source verification');

        $this->assertSame(ArticleStatus::ReturnedForRevision, $updated->status);
        $this->assertWorkflowEvent($updated, ArticleWorkflowAction::Return, ArticleStatus::PendingReview, ArticleStatus::ReturnedForRevision, $editor, 'Needs source verification');
    }

    public function test_return_unpublish_and_archive_require_comment(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $editor = $this->userWithRole(Rbac::NEWS_EDITOR);

        $this->expectException(ValidationException::class);

        app(ArticleWorkflowService::class)->returnForRevision(
            $editor,
            Article::factory()->create(['status' => ArticleStatus::PendingReview]),
            '',
        );
    }

    public function test_editor_can_schedule_approved_article_for_future_publication(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $editor = $this->userWithRole(Rbac::EDITOR_IN_CHIEF);
        $article = Article::factory()->create(['status' => ArticleStatus::Approved]);
        $scheduledAt = now()->addHours(2);

        $updated = app(ArticleWorkflowService::class)->schedule($editor, $article, $scheduledAt, 'Homepage lead at 8 PM');

        $this->assertSame(ArticleStatus::Scheduled, $updated->status);
        $this->assertSame($scheduledAt->getTimestamp(), $updated->scheduled_at->getTimestamp());
        $this->assertWorkflowEvent($updated, ArticleWorkflowAction::Schedule, ArticleStatus::Approved, ArticleStatus::Scheduled, $editor, 'Homepage lead at 8 PM');
    }

    public function test_schedule_requires_future_time(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->expectException(ValidationException::class);

        app(ArticleWorkflowService::class)->schedule(
            $this->userWithRole(Rbac::EDITOR_IN_CHIEF),
            Article::factory()->create(['status' => ArticleStatus::Approved]),
            now()->subMinute(),
        );
    }

    public function test_editor_can_publish_approved_or_scheduled_article(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $editor = $this->userWithRole(Rbac::NEWS_EDITOR);
        $article = Article::factory()->create(['status' => ArticleStatus::Approved]);

        $updated = app(ArticleWorkflowService::class)->publish($editor, $article, 'Publish now');

        $this->assertSame(ArticleStatus::Published, $updated->status);
        $this->assertSame($editor->getKey(), $updated->published_by);
        $this->assertNotNull($updated->published_at);
        $this->assertWorkflowEvent($updated, ArticleWorkflowAction::Publish, ArticleStatus::Approved, ArticleStatus::Published, $editor, 'Publish now');
    }

    public function test_published_article_cannot_return_to_draft_casually(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $editor = $this->userWithRole(Rbac::NEWS_EDITOR);
        $article = Article::factory()->published()->create();

        $this->expectException(ValidationException::class);

        app(ArticleWorkflowService::class)->submit($editor, $article);
    }

    public function test_editor_can_unpublish_then_republish_article_with_reason(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $editor = $this->userWithRole(Rbac::NEWS_EDITOR);
        $article = Article::factory()->published()->create();

        $unpublished = app(ArticleWorkflowService::class)->unpublish($editor, $article, 'Legal review');
        $this->assertSame(ArticleStatus::Unpublished, $unpublished->status);
        $this->assertWorkflowEvent($unpublished, ArticleWorkflowAction::Unpublish, ArticleStatus::Published, ArticleStatus::Unpublished, $editor, 'Legal review');

        $republished = app(ArticleWorkflowService::class)->publish($editor, $unpublished, 'Legal review cleared');

        $this->assertSame(ArticleStatus::Published, $republished->status);
        $this->assertWorkflowEvent($republished, ArticleWorkflowAction::Publish, ArticleStatus::Unpublished, ArticleStatus::Published, $editor, 'Legal review cleared');
    }

    public function test_editor_can_archive_article_with_reason(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $editor = $this->userWithRole(Rbac::NEWS_EDITOR);
        $article = Article::factory()->create(['status' => ArticleStatus::Approved]);

        $updated = app(ArticleWorkflowService::class)->archive($editor, $article, 'No longer relevant');

        $this->assertSame(ArticleStatus::Archived, $updated->status);
        $this->assertWorkflowEvent($updated, ArticleWorkflowAction::Archive, ArticleStatus::Approved, ArticleStatus::Archived, $editor, 'No longer relevant');
    }

    public function test_illegal_transition_is_denied_even_with_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $editor = $this->userWithRole(Rbac::NEWS_EDITOR);
        $draft = Article::factory()->create(['status' => ArticleStatus::Draft]);

        $this->expectException(ValidationException::class);

        app(ArticleWorkflowService::class)->approve($editor, $draft);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function assertWorkflowEvent(
        Article $article,
        ArticleWorkflowAction $action,
        ArticleStatus $previousStatus,
        ArticleStatus $newStatus,
        User $actor,
        ?string $comment,
    ): void {
        $this->assertDatabaseHas('article_workflow_events', [
            'article_id' => $article->getKey(),
            'action' => $action->value,
            'previous_status' => $previousStatus->value,
            'new_status' => $newStatus->value,
            'actor_id' => $actor->getKey(),
            'comment' => $comment,
        ]);
    }
}
