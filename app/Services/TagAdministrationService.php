<?php

namespace App\Services;

use App\Models\Tag;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TagAdministrationService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(mixed $actor, array $attributes): Tag
    {
        Gate::forUser($actor)->authorize('create', Tag::class);

        $tag = Tag::query()->create($this->validatedAttributes($attributes));

        Log::info('Tag administration: tag created', [
            'actor_id' => $actor?->getKey(),
            'tag_id' => $tag->getKey(),
        ]);

        return $tag;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(mixed $actor, Tag $tag, array $attributes): Tag
    {
        Gate::forUser($actor)->authorize('update', $tag);

        $tag->update($this->validatedAttributes($attributes, $tag));

        Log::info('Tag administration: tag updated', [
            'actor_id' => $actor?->getKey(),
            'tag_id' => $tag->getKey(),
            'changed' => array_keys($tag->getChanges()),
        ]);

        return $tag->refresh();
    }

    public function delete(mixed $actor, Tag $tag): void
    {
        Gate::forUser($actor)->authorize('delete', $tag);

        $tag->delete();

        Log::info('Tag administration: tag soft deleted', [
            'actor_id' => $actor?->getKey(),
            'tag_id' => $tag->getKey(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function validatedAttributes(array $attributes, ?Tag $tag = null): array
    {
        return Arr::only(Validator::make($attributes, [
            'name_bn' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('tags', 'slug')->ignore($tag)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ])->validate(), [
            'name_bn',
            'name_en',
            'slug',
            'status',
        ]);
    }
}
