<?php

namespace App\Services;

use App\Enums\VideoProvider;
use App\Enums\VideoStatus;
use App\Models\Video;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VideoAdministrationService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(mixed $actor, array $attributes): Video
    {
        Gate::forUser($actor)->authorize('create', Video::class);

        $attributes = $this->validatedAttributes($attributes);
        $attributes['created_by'] = $actor?->getKey();

        return Video::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(mixed $actor, Video $video, array $attributes): Video
    {
        Gate::forUser($actor)->authorize('update', $video);

        $attributes = $this->validatedAttributes($attributes);
        $attributes['updated_by'] = $actor?->getKey();

        $video->update($attributes);

        return $video->refresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function validatedAttributes(array $attributes): array
    {
        $validated = Validator::make($attributes, [
            'title_bn' => ['required', 'string', 'max:255'],
            'description_bn' => ['nullable', 'string'],
            'provider' => ['required', Rule::enum(VideoProvider::class)],
            'video_url' => ['required', 'string', 'max:2048'],
            'thumbnail' => ['nullable', 'url:https', 'max:2048'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'author_id' => ['nullable', 'integer', Rule::exists('authors', 'id')->whereNull('deleted_at')],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'status' => ['required', Rule::enum(VideoStatus::class)],
            'published_at' => ['nullable', 'date'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
        ])->validate();

        $providerPayload = app(VideoProviderService::class)->validatedProviderPayload($validated['provider'], $validated['video_url']);

        return array_merge($this->cleanPayload($validated), $providerPayload);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function cleanPayload(array $attributes): array
    {
        $payload = Arr::only($attributes, [
            'title_bn',
            'description_bn',
            'thumbnail',
            'duration',
            'author_id',
            'category_id',
            'status',
            'published_at',
            'seo_title',
            'seo_description',
        ]);

        foreach (['title_bn', 'description_bn', 'seo_title', 'seo_description'] as $field) {
            if (isset($payload[$field])) {
                $payload[$field] = $this->cleanText((string) $payload[$field]);
            }
        }

        return $payload;
    }

    private function cleanText(string $value): string
    {
        $value = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $value) ?? '';

        return trim(strip_tags($value));
    }
}
