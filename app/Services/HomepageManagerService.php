<?php

namespace App\Services;

use App\Enums\HomepageSectionKey;
use App\Enums\HomepageSectionStatus;
use App\Models\Article;
use App\Models\HomepageSection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HomepageManagerService
{
    public function __construct(private ActivityLogger $activityLogger) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(mixed $actor, array $attributes): HomepageSection
    {
        Gate::forUser($actor)->authorize('create', HomepageSection::class);

        $attributes = $this->validatedAttributes($attributes);
        $attributes['created_by'] = $actor?->getKey();

        return DB::transaction(function () use ($attributes, $actor): HomepageSection {
            $section = HomepageSection::query()->create($this->sectionPayload($attributes));
            $this->syncItems($section, $attributes['items'] ?? []);
            $section = $section->refresh()->load(['items.article']);
            $this->activityLogger->record(
                'homepage.create',
                $actor,
                $section,
                newValues: $this->auditValues($section),
            );
            $this->log('created', $actor, $section);
            $this->forgetPublicCache();

            return $section;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(mixed $actor, HomepageSection $section, array $attributes): HomepageSection
    {
        Gate::forUser($actor)->authorize('update', $section);

        $attributes = $this->validatedAttributes($attributes, $section);
        $attributes['updated_by'] = $actor?->getKey();

        $oldValues = $this->auditValues($section->loadMissing('items'));

        return DB::transaction(function () use ($section, $attributes, $actor, $oldValues): HomepageSection {
            $section->update($this->sectionPayload($attributes));
            $this->syncItems($section, $attributes['items'] ?? []);
            $section = $section->refresh()->load(['items.article']);
            $diff = $this->activityLogger->safeDiff($oldValues, $this->auditValues($section));
            $this->activityLogger->record(
                'homepage.change',
                $actor,
                $section,
                oldValues: $diff['old'],
                newValues: $diff['new'],
            );
            $this->log('updated', $actor, $section);
            $this->forgetPublicCache();

            return $section;
        });
    }

    public function publish(mixed $actor, HomepageSection $section): HomepageSection
    {
        Gate::forUser($actor)->authorize('update', $section);

        $oldStatus = $section->status;

        $section->forceFill([
            'status' => HomepageSectionStatus::Published,
            'published_at' => now(),
            'published_by' => $actor?->getKey(),
        ])->save();

        $this->activityLogger->record(
            'homepage.publish',
            $actor,
            $section,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => HomepageSectionStatus::Published],
        );

        $this->log('published', $actor, $section);
        $this->forgetPublicCache();

        return $section->refresh();
    }

    /**
     * @param  array<int, int>  $orderedItemIds
     */
    public function reorderItems(mixed $actor, HomepageSection $section, array $orderedItemIds): HomepageSection
    {
        Gate::forUser($actor)->authorize('update', $section);

        $existingIds = $section->items()->pluck('id')->map(fn (int $id): int => $id)->all();
        $orderedItemIds = array_values(array_map('intval', $orderedItemIds));

        sort($existingIds);
        $requestedIds = $orderedItemIds;
        sort($requestedIds);

        if ($existingIds !== $requestedIds) {
            throw ValidationException::withMessages([
                'items' => 'Homepage items can only be reordered within their own section.',
            ]);
        }

        foreach ($orderedItemIds as $index => $itemId) {
            $section->items()->whereKey($itemId)->update(['sort_order' => $index]);
        }

        $this->activityLogger->record(
            'homepage.reorder',
            $actor,
            $section,
            newValues: ['item_ids' => $orderedItemIds],
        );

        $this->log('reordered', $actor, $section);
        $this->forgetPublicCache();

        return $section->refresh()->load(['items.article']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function validatedAttributes(array $attributes, ?HomepageSection $section = null): array
    {
        $validated = Validator::make($attributes, [
            'key' => ['required', Rule::enum(HomepageSectionKey::class), Rule::unique('homepage_sections', 'key')->ignore($section)],
            'title' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(HomepageSectionStatus::class)],
            'sort_order' => ['required', 'integer', 'min:0'],
            'items' => ['nullable', 'array'],
            'items.*.article_id' => ['required_with:items', 'integer', Rule::exists('articles', 'id')->whereNull('deleted_at')],
            'items.*.label' => ['nullable', 'string', 'max:255'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'items.*.starts_at' => ['nullable', 'date'],
            'items.*.ends_at' => ['nullable', 'date', 'after:items.*.starts_at'],
        ])->validate();

        $this->validateItems($validated);

        return $validated;
    }

    /** @param array<string, mixed> $attributes */
    private function validateItems(array $attributes): void
    {
        $items = $attributes['items'] ?? [];
        $articleIds = array_map(fn (array $item): int => (int) $item['article_id'], $items);

        if (count($articleIds) !== count(array_unique($articleIds))) {
            throw ValidationException::withMessages(['items' => 'A story may only appear once in a homepage section.']);
        }

        if (in_array($attributes['key'], HomepageSectionKey::singleArticleSlots(), true) && count($items) > 1) {
            throw ValidationException::withMessages(['items' => 'This homepage position accepts only one story.']);
        }

        $publicArticleCount = Article::query()
            ->publiclyVisible()
            ->whereIn('id', $articleIds)
            ->count();

        if ($publicArticleCount !== count($articleIds)) {
            throw ValidationException::withMessages(['items' => 'Only published public stories may be placed on the homepage.']);
        }
    }

    /** @param array<string, mixed> $attributes */
    private function sectionPayload(array $attributes): array
    {
        return Arr::only($attributes, [
            'key',
            'title',
            'status',
            'sort_order',
            'created_by',
            'updated_by',
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncItems(HomepageSection $section, array $items): void
    {
        $section->items()->delete();

        foreach (array_values($items) as $index => $item) {
            $section->items()->create([
                'article_id' => $item['article_id'],
                'label' => $item['label'] ?? null,
                'sort_order' => $index,
                'starts_at' => $item['starts_at'] ?? null,
                'ends_at' => $item['ends_at'] ?? null,
            ]);
        }
    }

    private function log(string $action, mixed $actor, HomepageSection $section): void
    {
        Log::info('Homepage manager activity', [
            'action' => $action,
            'actor_id' => $actor?->getKey(),
            'homepage_section_id' => $section->getKey(),
            'section_key' => $section->key?->value ?? $section->key,
        ]);
    }

    private function forgetPublicCache(): void
    {
        Cache::forget('public-homepage-v1');
    }

    /** @return array<string, mixed> */
    private function auditValues(HomepageSection $section): array
    {
        return [
            'key' => $section->key,
            'title' => $section->title,
            'status' => $section->status,
            'sort_order' => $section->sort_order,
            'item_ids' => $section->items->pluck('article_id')->values()->all(),
        ];
    }
}
