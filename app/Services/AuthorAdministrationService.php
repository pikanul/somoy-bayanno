<?php

namespace App\Services;

use App\Models\Author;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthorAdministrationService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(mixed $actor, array $attributes): Author
    {
        Gate::forUser($actor)->authorize('create', Author::class);

        $author = Author::query()->create($this->validatedAttributes($attributes));

        Log::info('Author administration: author created', [
            'actor_id' => $actor?->getKey(),
            'author_id' => $author->getKey(),
        ]);

        return $author;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(mixed $actor, Author $author, array $attributes): Author
    {
        Gate::forUser($actor)->authorize('update', $author);

        $author->update($this->validatedAttributes($attributes, $author));

        Log::info('Author administration: author updated', [
            'actor_id' => $actor?->getKey(),
            'author_id' => $author->getKey(),
            'changed' => array_keys($author->getChanges()),
        ]);

        return $author->refresh();
    }

    public function delete(mixed $actor, Author $author): void
    {
        Gate::forUser($actor)->authorize('delete', $author);

        $author->delete();

        Log::info('Author administration: author soft deleted', [
            'actor_id' => $actor?->getKey(),
            'author_id' => $author->getKey(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function validatedAttributes(array $attributes, ?Author $author = null): array
    {
        $attributes = array_merge([
            'organization_level' => $author?->organization_level ?? 3,
            'sort_order' => $author?->sort_order ?? 100,
        ], $attributes);

        return Arr::only(Validator::make($attributes, [
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'name_bn' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('authors', 'slug')->ignore($author)],
            'designation' => ['nullable', 'string', 'max:255'],
            'bio_bn' => ['nullable', 'string'],
            'bio_en' => ['nullable', 'string'],
            'photo' => ['nullable', 'string', 'max:2048'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:2048'],
            'x_url' => ['nullable', 'url', 'max:2048'],
            'linkedin_url' => ['nullable', 'url', 'max:2048'],
            'website_url' => ['nullable', 'url', 'max:2048'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'featured' => ['required', 'boolean'],
            'organization_level' => ['required', 'integer', 'min:1', 'max:4'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
        ])->validate(), [
            'user_id',
            'name_bn',
            'name_en',
            'slug',
            'designation',
            'bio_bn',
            'bio_en',
            'photo',
            'email',
            'phone',
            'address',
            'facebook_url',
            'x_url',
            'linkedin_url',
            'website_url',
            'status',
            'featured',
            'organization_level',
            'sort_order',
            'seo_title',
            'seo_description',
        ]);
    }
}
