<?php

namespace App\Services;

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Enums\ArticleVisibility;
use App\Models\Article;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ArticleAdministrationService
{
    public function __construct(private ActivityLogger $activityLogger) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $actor, array $attributes): Article
    {
        Gate::forUser($actor)->authorize('create', Article::class);

        $attributes = $this->validatedAttributes($attributes);
        $this->ensureCreateStartsAsDraft($attributes);
        $this->authorizePublicationControls($actor, $attributes);

        return DB::transaction(function () use ($actor, $attributes): Article {
            $article = Article::query()->create($this->articlePayload($attributes) + [
                'created_by' => $actor->getKey(),
                'updated_by' => $actor->getKey(),
                'published_by' => $this->publishedBy($actor, $attributes),
            ]);

            $this->syncRelationships($article, $attributes);
            $this->createRevision($article, $actor, 'Article created.');
            $article = $article->refresh();

            $this->activityLogger->record(
                'article.create',
                $actor,
                $article,
                newValues: $this->auditValues($article, $attributes),
            );

            Log::info('Article administration: article created', [
                'actor_id' => $actor->getKey(),
                'article_id' => $article->getKey(),
            ]);

            return $article;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $actor, Article $article, array $attributes): Article
    {
        Gate::forUser($actor)->authorize('update', $article);

        $attributes = $this->validatedAttributes($attributes, $article);
        $this->preventDirectStatusTransition($article, $attributes);
        $this->authorizePublicationControls($actor, $attributes, $article);

        $oldValues = $this->auditValues($article, $attributes);

        return DB::transaction(function () use ($actor, $article, $attributes, $oldValues): Article {
            $article->update($this->articlePayload($attributes) + [
                'updated_by' => $actor->getKey(),
                'published_by' => $this->publishedBy($actor, $attributes, $article),
            ]);

            $this->syncRelationships($article, $attributes);
            $article = $article->refresh();
            $this->createRevision($article, $actor, 'Article updated.');

            $diff = $this->activityLogger->safeDiff($oldValues, $this->auditValues($article, $attributes));
            $this->activityLogger->record(
                'article.update',
                $actor,
                $article,
                oldValues: $diff['old'],
                newValues: $diff['new'],
            );

            Log::info('Article administration: article updated', [
                'actor_id' => $actor->getKey(),
                'article_id' => $article->getKey(),
                'changed' => array_keys($article->getChanges()),
            ]);

            return $article;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function validatedAttributes(array $attributes, ?Article $article = null): array
    {
        $typeValues = array_column(ArticleType::cases(), 'value');
        $statusValues = array_column(ArticleStatus::cases(), 'value');
        $visibilityValues = array_column(ArticleVisibility::cases(), 'value');

        $validated = Validator::make($attributes, [
            'type' => ['required', Rule::in($typeValues)],
            'headline_bn' => ['required', 'string', 'max:255'],
            'headline_en' => ['nullable', 'string', 'max:255'],
            'short_headline_bn' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('articles', 'slug')->ignore($article)],
            'subheadline_bn' => ['nullable', 'string', 'max:255'],
            'summary_bn' => ['nullable', 'string'],
            'body_bn' => ['required', 'string'],
            'primary_category_id' => ['required', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', Rule::exists('tags', 'id')->whereNull('deleted_at')],
            'topic_ids' => ['nullable', 'array'],
            'topic_ids.*' => ['integer', Rule::exists('topics', 'id')->whereNull('deleted_at')],
            'author_ids' => ['nullable', 'array'],
            'author_ids.*' => ['integer', Rule::exists('authors', 'id')->whereNull('deleted_at')],
            'reporter_name' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'featured_media_id' => ['nullable', 'integer', Rule::exists('media_assets', 'id')],
            'featured_image_url' => ['nullable', 'url', 'max:2048'],
            'image_caption' => ['nullable', 'string', 'max:255'],
            'image_credit' => ['nullable', 'string', 'max:255'],
            'video_url' => ['nullable', 'url', 'max:2048'],
            'status' => ['required', Rule::in($statusValues)],
            'visibility' => ['required', Rule::in($visibilityValues)],
            'comments_enabled' => ['required', 'boolean'],
            'is_breaking' => ['required', 'boolean'],
            'is_featured' => ['required', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date'],
            'updated_content_at' => ['nullable', 'date'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
            'canonical_url' => ['nullable', 'url', 'max:2048'],
            'social_title' => ['nullable', 'string', 'max:255'],
            'social_description' => ['nullable', 'string'],
            'social_image' => ['nullable', 'url', 'max:2048'],
            'correction_note' => ['nullable', 'string'],
            'internal_editor_note' => ['nullable', 'string'],
        ])->validate();

        $validated['category_ids'] = array_values(array_unique([
            (int) $validated['primary_category_id'],
            ...array_map('intval', $validated['category_ids'] ?? []),
        ]));
        $validated['tag_ids'] = array_values(array_unique(array_map('intval', $validated['tag_ids'] ?? [])));
        $validated['topic_ids'] = array_values(array_unique(array_map('intval', $validated['topic_ids'] ?? [])));
        $validated['author_ids'] = array_values(array_unique(array_map('intval', $validated['author_ids'] ?? [])));
        $validated['body_bn'] = $this->sanitizeRichText($validated['body_bn']);

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function authorizePublicationControls(User $actor, array $attributes, ?Article $article = null): void
    {
        $publishStatuses = [
            ArticleStatus::Approved->value,
            ArticleStatus::Scheduled->value,
            ArticleStatus::Published->value,
            ArticleStatus::Updated->value,
            ArticleStatus::Unpublished->value,
            ArticleStatus::Archived->value,
        ];

        $requiresPublishPermission = in_array($attributes['status'], $publishStatuses, true)
            || ! empty($attributes['published_at'])
            || ! empty($attributes['scheduled_at'])
            || (bool) $attributes['is_breaking']
            || (bool) $attributes['is_featured'];

        if (! $requiresPublishPermission) {
            $this->authorizeInternalEditorNote($actor, $attributes);

            return;
        }

        $allowed = $article
            ? Gate::forUser($actor)->allows('publish', $article)
            : $actor->can('article.publish');

        if (! $allowed) {
            throw ValidationException::withMessages([
                'status' => 'You do not have permission to use publication controls.',
            ]);
        }

        $this->authorizeInternalEditorNote($actor, $attributes);
    }

    /** @param  array<string, mixed>  $attributes */
    private function ensureCreateStartsAsDraft(array $attributes): void
    {
        if ($attributes['status'] === ArticleStatus::Draft->value) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => 'Articles must start as Draft. Use workflow actions to change editorial status.',
        ]);
    }

    /** @param  array<string, mixed>  $attributes */
    private function preventDirectStatusTransition(Article $article, array $attributes): void
    {
        if ($article->status->value === $attributes['status']) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => 'Use workflow actions to change editorial status.',
        ]);
    }

    /** @param  array<string, mixed>  $attributes */
    private function authorizeInternalEditorNote(User $actor, array $attributes): void
    {
        if (blank($attributes['internal_editor_note'] ?? null)) {
            return;
        }

        if ($actor->can('article.review') || $actor->can('article.publish')) {
            return;
        }

        throw ValidationException::withMessages([
            'internal_editor_note' => 'You do not have permission to manage internal editor notes.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function articlePayload(array $attributes): array
    {
        return Arr::only($attributes, [
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
            'featured_media_id',
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
            'internal_editor_note',
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function syncRelationships(Article $article, array $attributes): void
    {
        $article->categories()->sync($this->categorySyncPayload($attributes));
        $article->tags()->sync($attributes['tag_ids']);
        $article->topics()->sync($attributes['topic_ids']);
        $article->authors()->sync($this->orderedSyncPayload($attributes['author_ids'], 'Reporter'));
    }

    /** @param array<string, mixed> $attributes */
    private function categorySyncPayload(array $attributes): array
    {
        $payload = [];

        foreach ($attributes['category_ids'] as $index => $categoryId) {
            $payload[$categoryId] = [
                'is_primary' => (int) $categoryId === (int) $attributes['primary_category_id'],
                'sort_order' => $index,
            ];
        }

        return $payload;
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, array<string, mixed>>
     */
    private function orderedSyncPayload(array $ids, ?string $credit = null): array
    {
        $payload = [];

        foreach ($ids as $index => $id) {
            $payload[$id] = array_filter([
                'credit' => $credit,
                'sort_order' => $index,
            ], fn (mixed $value): bool => $value !== null);
        }

        return $payload;
    }

    private function createRevision(Article $article, User $actor, string $summary): void
    {
        app(ArticleRevisionService::class)->recordSnapshot($article->fresh(), $actor, $summary);
    }

    /** @param array<string, mixed> $attributes */
    private function publishedBy(User $actor, array $attributes, ?Article $article = null): ?int
    {
        if (in_array($attributes['status'], [ArticleStatus::Published->value, ArticleStatus::Updated->value], true)) {
            return $actor->getKey();
        }

        return $article?->published_by;
    }

    private function sanitizeRichText(string $content): string
    {
        $content = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $content) ?? '';
        $content = preg_replace('/\s+on[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $content) ?? '';
        $content = preg_replace('/(href|src)\s*=\s*([\'"])\s*javascript:.*?\2/i', '$1="#"', $content) ?? '';

        return strip_tags($content, '<p><br><strong><b><em><i><u><s><blockquote><ul><ol><li><a><h2><h3><h4>');
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function auditValues(Article $article, array $attributes): array
    {
        return [
            'type' => $article->type,
            'headline_bn' => $article->headline_bn,
            'slug' => $article->slug,
            'primary_category_id' => $article->primary_category_id,
            'category_ids' => $attributes['category_ids'] ?? $article->categories()->pluck('categories.id')->all(),
            'tag_ids' => $attributes['tag_ids'] ?? $article->tags()->pluck('tags.id')->all(),
            'topic_ids' => $attributes['topic_ids'] ?? $article->topics()->pluck('topics.id')->all(),
            'author_ids' => $attributes['author_ids'] ?? $article->authors()->pluck('authors.id')->all(),
            'status' => $article->status,
            'visibility' => $article->visibility,
            'is_breaking' => $article->is_breaking,
            'is_featured' => $article->is_featured,
            'published_at' => $article->published_at,
            'scheduled_at' => $article->scheduled_at,
            'seo_title' => $article->seo_title,
            'seo_description' => $article->seo_description,
            'canonical_url' => $article->canonical_url,
        ];
    }
}
