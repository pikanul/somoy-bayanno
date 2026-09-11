<?php

namespace App\Services;

use App\Enums\GalleryStatus;
use App\Models\Gallery;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GalleryAdministrationService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(mixed $actor, array $attributes): Gallery
    {
        Gate::forUser($actor)->authorize('create', Gallery::class);

        $attributes = $this->validatedAttributes($attributes);
        $attributes['created_by'] = $actor?->getKey();

        return DB::transaction(function () use ($attributes): Gallery {
            $gallery = Gallery::query()->create($this->galleryPayload($attributes));
            $this->syncItems($gallery, $attributes['items'] ?? []);

            return $gallery->refresh()->load(['items.media']);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(mixed $actor, Gallery $gallery, array $attributes): Gallery
    {
        Gate::forUser($actor)->authorize('update', $gallery);

        $attributes = $this->validatedAttributes($attributes, $gallery);
        $attributes['updated_by'] = $actor?->getKey();

        return DB::transaction(function () use ($gallery, $attributes): Gallery {
            $gallery->update($this->galleryPayload($attributes));
            $this->syncItems($gallery, $attributes['items'] ?? []);

            return $gallery->refresh()->load(['items.media']);
        });
    }

    /**
     * @param  array<int, int>  $orderedItemIds
     */
    public function reorderItems(mixed $actor, Gallery $gallery, array $orderedItemIds): Gallery
    {
        Gate::forUser($actor)->authorize('update', $gallery);

        $existingIds = $gallery->items()->pluck('id')->map(fn (int $id): int => $id)->all();
        $orderedItemIds = array_values(array_map('intval', $orderedItemIds));

        sort($existingIds);
        $sortedRequestedIds = $orderedItemIds;
        sort($sortedRequestedIds);

        if ($existingIds !== $sortedRequestedIds) {
            throw ValidationException::withMessages([
                'items' => 'Gallery items can only be reordered within the current gallery.',
            ]);
        }

        foreach ($orderedItemIds as $index => $itemId) {
            $gallery->items()->whereKey($itemId)->update(['sort_order' => $index]);
        }

        return $gallery->refresh()->load(['items.media']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function validatedAttributes(array $attributes, ?Gallery $gallery = null): array
    {
        $validated = Validator::make($attributes, [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('galleries', 'slug')->ignore($gallery)],
            'description' => ['nullable', 'string'],
            'cover_image_id' => ['nullable', 'integer', Rule::exists('media_assets', 'id')->where('type', 'image')],
            'author_id' => ['nullable', 'integer', Rule::exists('authors', 'id')->whereNull('deleted_at')],
            'photographer' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'status' => ['required', Rule::enum(GalleryStatus::class)],
            'published_at' => ['nullable', 'date'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.media_id' => ['required_with:items', 'integer', Rule::exists('media_assets', 'id')->where('type', 'image')],
            'items.*.caption' => ['nullable', 'string', 'max:255'],
            'items.*.credit' => ['nullable', 'string', 'max:255'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ])->validate();

        $this->ensureUniqueMediaItems($validated['items'] ?? []);

        return $this->cleanPayload($validated);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function ensureUniqueMediaItems(array $items): void
    {
        $mediaIds = array_map(fn (array $item): int => (int) $item['media_id'], $items);

        if (count($mediaIds) !== count(array_unique($mediaIds))) {
            throw ValidationException::withMessages([
                'items' => 'A media item may only appear once in a gallery.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function cleanPayload(array $attributes): array
    {
        foreach (['title', 'description', 'photographer', 'seo_title', 'seo_description'] as $field) {
            if (isset($attributes[$field])) {
                $attributes[$field] = $this->cleanText((string) $attributes[$field]);
            }
        }

        foreach (($attributes['items'] ?? []) as $index => $item) {
            foreach (['caption', 'credit'] as $field) {
                if (isset($item[$field])) {
                    $attributes['items'][$index][$field] = $this->cleanText((string) $item[$field]);
                }
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function galleryPayload(array $attributes): array
    {
        return Arr::only($attributes, [
            'title',
            'slug',
            'description',
            'cover_image_id',
            'author_id',
            'photographer',
            'category_id',
            'status',
            'published_at',
            'seo_title',
            'seo_description',
            'created_by',
            'updated_by',
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncItems(Gallery $gallery, array $items): void
    {
        $gallery->items()->delete();

        foreach (array_values($items) as $index => $item) {
            $gallery->items()->create([
                'media_id' => $item['media_id'],
                'caption' => $item['caption'] ?? null,
                'credit' => $item['credit'] ?? null,
                'sort_order' => $index,
            ]);
        }
    }

    private function cleanText(string $value): string
    {
        $value = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $value) ?? '';

        return trim(strip_tags($value));
    }
}
