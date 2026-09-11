<?php

namespace App\Services;

use App\Models\Topic;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TopicAdministrationService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(mixed $actor, array $attributes): Topic
    {
        Gate::forUser($actor)->authorize('create', Topic::class);

        $topic = Topic::query()->create($this->validatedAttributes($attributes));

        Log::info('Topic administration: topic created', [
            'actor_id' => $actor?->getKey(),
            'topic_id' => $topic->getKey(),
        ]);

        return $topic;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(mixed $actor, Topic $topic, array $attributes): Topic
    {
        Gate::forUser($actor)->authorize('update', $topic);

        $topic->update($this->validatedAttributes($attributes, $topic));

        Log::info('Topic administration: topic updated', [
            'actor_id' => $actor?->getKey(),
            'topic_id' => $topic->getKey(),
            'changed' => array_keys($topic->getChanges()),
        ]);

        return $topic->refresh();
    }

    public function delete(mixed $actor, Topic $topic): void
    {
        Gate::forUser($actor)->authorize('delete', $topic);

        $topic->delete();

        Log::info('Topic administration: topic soft deleted', [
            'actor_id' => $actor?->getKey(),
            'topic_id' => $topic->getKey(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function validatedAttributes(array $attributes, ?Topic $topic = null): array
    {
        return Arr::only(Validator::make($attributes, [
            'name_bn' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('topics', 'slug')->ignore($topic)],
            'description' => ['nullable', 'string'],
            'featured' => ['required', 'boolean'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
        ])->validate(), [
            'name_bn',
            'name_en',
            'slug',
            'description',
            'featured',
            'status',
            'seo_title',
            'seo_description',
        ]);
    }
}
