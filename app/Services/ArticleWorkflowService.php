<?php

namespace App\Services;

use App\Enums\ArticleStatus;
use App\Enums\ArticleWorkflowAction;
use App\Models\Article;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ArticleWorkflowService
{
    public function __construct(private ActivityLogger $activityLogger) {}

    public function submit(User $actor, Article $article, ?string $comment = null): Article
    {
        Gate::forUser($actor)->authorize('submit', $article);

        return $this->transition(
            actor: $actor,
            article: $article,
            action: ArticleWorkflowAction::Submit,
            allowedFrom: [ArticleStatus::Draft, ArticleStatus::ReturnedForRevision],
            newStatus: ArticleStatus::PendingReview,
            comment: $comment,
        );
    }

    public function returnForRevision(User $actor, Article $article, string $comment): Article
    {
        Gate::forUser($actor)->authorize('returnForRevision', $article);

        return $this->transition(
            actor: $actor,
            article: $article,
            action: ArticleWorkflowAction::Return,
            allowedFrom: [ArticleStatus::PendingReview, ArticleStatus::Approved],
            newStatus: ArticleStatus::ReturnedForRevision,
            comment: $comment,
            commentRequired: true,
        );
    }

    public function approve(User $actor, Article $article, ?string $comment = null): Article
    {
        Gate::forUser($actor)->authorize('approve', $article);

        return $this->transition(
            actor: $actor,
            article: $article,
            action: ArticleWorkflowAction::Approve,
            allowedFrom: [ArticleStatus::PendingReview],
            newStatus: ArticleStatus::Approved,
            comment: $comment,
        );
    }

    public function schedule(User $actor, Article $article, mixed $scheduledAt, ?string $comment = null): Article
    {
        Gate::forUser($actor)->authorize('schedule', $article);

        $scheduledAt = rescue(fn () => Carbon::parse($scheduledAt), report: false);

        if (! $scheduledAt || $scheduledAt->lte(now())) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Scheduled publish time must be in the future.',
            ]);
        }

        return $this->transition(
            actor: $actor,
            article: $article,
            action: ArticleWorkflowAction::Schedule,
            allowedFrom: [ArticleStatus::Approved],
            newStatus: ArticleStatus::Scheduled,
            comment: $comment,
            attributes: ['scheduled_at' => $scheduledAt],
        );
    }

    public function publish(User $actor, Article $article, ?string $comment = null): Article
    {
        Gate::forUser($actor)->authorize('publish', $article);

        return $this->transition(
            actor: $actor,
            article: $article,
            action: ArticleWorkflowAction::Publish,
            allowedFrom: [ArticleStatus::Approved, ArticleStatus::Scheduled, ArticleStatus::Unpublished],
            newStatus: ArticleStatus::Published,
            comment: $comment,
            attributes: [
                'published_at' => $article->published_at ?? now(),
                'published_by' => $actor->getKey(),
                'scheduled_at' => null,
            ],
        );
    }

    public function unpublish(User $actor, Article $article, string $comment): Article
    {
        Gate::forUser($actor)->authorize('unpublish', $article);

        return $this->transition(
            actor: $actor,
            article: $article,
            action: ArticleWorkflowAction::Unpublish,
            allowedFrom: [ArticleStatus::Published, ArticleStatus::Updated, ArticleStatus::Scheduled],
            newStatus: ArticleStatus::Unpublished,
            comment: $comment,
            commentRequired: true,
        );
    }

    public function archive(User $actor, Article $article, string $comment): Article
    {
        Gate::forUser($actor)->authorize('archive', $article);

        return $this->transition(
            actor: $actor,
            article: $article,
            action: ArticleWorkflowAction::Archive,
            allowedFrom: [
                ArticleStatus::Draft,
                ArticleStatus::PendingReview,
                ArticleStatus::ReturnedForRevision,
                ArticleStatus::Approved,
                ArticleStatus::Scheduled,
                ArticleStatus::Published,
                ArticleStatus::Updated,
                ArticleStatus::Unpublished,
                ArticleStatus::Rejected,
            ],
            newStatus: ArticleStatus::Archived,
            comment: $comment,
            commentRequired: true,
        );
    }

    /**
     * @param  array<int, ArticleStatus>  $allowedFrom
     * @param  array<string, mixed>  $attributes
     */
    private function transition(
        User $actor,
        Article $article,
        ArticleWorkflowAction $action,
        array $allowedFrom,
        ArticleStatus $newStatus,
        ?string $comment = null,
        bool $commentRequired = false,
        array $attributes = [],
    ): Article {
        $previousStatus = $article->status;

        if (! in_array($previousStatus, $allowedFrom, true)) {
            throw ValidationException::withMessages([
                'status' => "Cannot {$action->value} an article from {$previousStatus->label()}.",
            ]);
        }

        if ($commentRequired && blank($comment)) {
            throw ValidationException::withMessages([
                'comment' => 'A comment or reason is required for this workflow action.',
            ]);
        }

        return DB::transaction(function () use ($actor, $article, $action, $previousStatus, $newStatus, $comment, $attributes): Article {
            $article->forceFill($attributes + [
                'status' => $newStatus,
                'updated_by' => $actor->getKey(),
                'updated_content_at' => now(),
            ])->save();

            $article->workflowEvents()->create([
                'action' => $action,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'comment' => $comment,
                'actor_id' => $actor->getKey(),
                'acted_at' => now(),
            ]);

            $this->activityLogger->record(
                'article.'.$action->value,
                $actor,
                $article,
                oldValues: ['status' => $previousStatus],
                newValues: ['status' => $newStatus] + $attributes,
                properties: ['comment' => $comment],
            );

            Log::info('Article workflow: transition recorded', [
                'actor_id' => $actor->getKey(),
                'article_id' => $article->getKey(),
                'action' => $action->value,
                'previous_status' => $previousStatus->value,
                'new_status' => $newStatus->value,
            ]);

            return $article->refresh();
        });
    }
}
