<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ArticleRevisionService
{
    /** @var array<int, string> */
    private const SNAPSHOT_FIELDS = [
        'type',
        'headline_bn',
        'headline_en',
        'short_headline_bn',
        'slug',
        'subheadline_bn',
        'summary_bn',
        'body_bn',
        'primary_category_id',
        'reporter_name',
        'location',
        'source_name',
        'source_url',
        'featured_image_url',
        'image_caption',
        'image_credit',
        'video_url',
        'status',
        'visibility',
        'comments_enabled',
        'is_breaking',
        'is_featured',
        'published_at',
        'scheduled_at',
        'updated_content_at',
        'seo_title',
        'seo_description',
        'canonical_url',
        'social_title',
        'social_description',
        'social_image',
        'correction_note',
    ];

    public function recordSnapshot(Article $article, User $actor, string $summary): ArticleRevision
    {
        $article->loadMissing(['categories', 'tags', 'topics', 'authors']);
        $nextVersion = max(
            (int) $article->revisions()->max('version'),
            (int) $article->revisions()->max('revision_number'),
        ) + 1;

        return $article->revisions()->create([
            'revision_number' => $nextVersion,
            'version' => $nextVersion,
            'snapshot' => $this->snapshot($article),
            'change_summary' => $summary,
            'changed_by' => $actor->getKey(),
            'created_by' => $actor->getKey(),
        ]);
    }

    /** @return array<string, array{from: mixed, to: mixed}> */
    public function compare(ArticleRevision $revision, ?ArticleRevision $previousRevision = null): array
    {
        $previousRevision ??= ArticleRevision::query()
            ->where('article_id', $revision->article_id)
            ->where('revision_number', '<', $revision->revision_number)
            ->orderByDesc('revision_number')
            ->first();

        return $revision->compareWith($previousRevision);
    }

    public function restore(User $actor, ArticleRevision $revision, string $reason): Article
    {
        $article = $revision->article()->firstOrFail();

        Gate::forUser($actor)->authorize('restoreRevision', $article);

        if (blank($reason)) {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required when restoring a revision.',
            ]);
        }

        return DB::transaction(function () use ($actor, $article, $revision, $reason): Article {
            $this->recordSnapshot($article, $actor, "Before restoring revision {$revision->version}: {$reason}");

            $snapshot = $revision->snapshot;

            $article->update(Arr::only($snapshot, self::SNAPSHOT_FIELDS) + [
                'updated_by' => $actor->getKey(),
                'updated_content_at' => now(),
            ]);

            $this->restoreRelationships($article, $snapshot);
            $this->recordSnapshot($article->refresh(), $actor, "Restored revision {$revision->version}: {$reason}");

            Log::info('Article revision: revision restored', [
                'actor_id' => $actor->getKey(),
                'article_id' => $article->getKey(),
                'restored_revision_id' => $revision->getKey(),
                'restored_version' => $revision->version,
            ]);

            return $article->refresh();
        });
    }

    /** @return array<string, mixed> */
    private function snapshot(Article $article): array
    {
        $attributes = Arr::only($article->attributesToArray(), self::SNAPSHOT_FIELDS);

        return $attributes + [
            'category_ids' => $article->categories->pluck('id')->values()->all(),
            'tag_ids' => $article->tags->pluck('id')->values()->all(),
            'topic_ids' => $article->topics->pluck('id')->values()->all(),
            'author_ids' => $article->authors->pluck('id')->values()->all(),
        ];
    }

    /** @param array<string, mixed> $snapshot */
    private function restoreRelationships(Article $article, array $snapshot): void
    {
        $article->categories()->sync($this->categorySyncPayload($snapshot));
        $article->tags()->sync($snapshot['tag_ids'] ?? []);
        $article->topics()->sync($snapshot['topic_ids'] ?? []);
        $article->authors()->sync($this->authorSyncPayload($snapshot['author_ids'] ?? []));
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<int, array<string, mixed>>
     */
    private function categorySyncPayload(array $snapshot): array
    {
        $payload = [];
        $ids = array_values(array_unique(array_map('intval', $snapshot['category_ids'] ?? [])));
        $primaryId = $snapshot['primary_category_id'] ?? null;

        if ($primaryId !== null && ! in_array((int) $primaryId, $ids, true)) {
            array_unshift($ids, (int) $primaryId);
        }

        foreach ($ids as $index => $id) {
            $payload[$id] = [
                'sort_order' => $index,
                'is_primary' => $primaryId !== null && (int) $primaryId === $id,
            ];
        }

        return $payload;
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, array<string, mixed>>
     */
    private function authorSyncPayload(array $ids): array
    {
        $payload = [];

        foreach (array_values(array_unique(array_map('intval', $ids))) as $index => $id) {
            $payload[$id] = [
                'credit' => 'Reporter',
                'sort_order' => $index,
            ];
        }

        return $payload;
    }
}
