<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CategoryAdministrationService
{
    public function __construct(private ActivityLogger $activityLogger) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(mixed $actor, array $attributes): Category
    {
        Gate::forUser($actor)->authorize('create', Category::class);

        $attributes = $this->validatedAttributes($attributes);

        $category = Category::query()->create($this->payload($attributes));

        $this->activityLogger->record(
            'category.create',
            $actor,
            $category,
            newValues: $category->only($this->auditKeys()),
        );

        Log::info('Category administration: category created', [
            'actor_id' => $actor?->getKey(),
            'category_id' => $category->getKey(),
        ]);

        return $category;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(mixed $actor, Category $category, array $attributes): Category
    {
        Gate::forUser($actor)->authorize('update', $category);

        $attributes = $this->validatedAttributes($attributes, $category);

        $oldValues = $category->only($this->auditKeys());

        $category->update($this->payload($attributes));
        $category = $category->refresh();

        $diff = $this->activityLogger->safeDiff($oldValues, $category->only($this->auditKeys()));
        $this->activityLogger->record(
            'category.change',
            $actor,
            $category,
            oldValues: $diff['old'],
            newValues: $diff['new'],
        );

        Log::info('Category administration: category updated', [
            'actor_id' => $actor?->getKey(),
            'category_id' => $category->getKey(),
            'changed' => array_keys($category->getChanges()),
        ]);

        return $category;
    }

    public function delete(mixed $actor, Category $category): void
    {
        Gate::forUser($actor)->authorize('delete', $category);

        $category->delete();

        $this->activityLogger->record('category.delete', $actor, $category);

        Log::info('Category administration: category soft deleted', [
            'actor_id' => $actor?->getKey(),
            'category_id' => $category->getKey(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function validatedAttributes(array $attributes, ?Category $category = null): array
    {
        $validated = Validator::make($attributes, [
            'name_bn' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique('categories', 'slug')->ignore($category)],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['required', 'integer', 'min:0'],
            'show_in_menu' => ['required', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
        ])->validate();

        $this->validateHierarchy($validated, $category);

        return $validated;
    }

    /** @param  array<string, mixed>  $attributes */
    private function validateHierarchy(array $attributes, ?Category $category): void
    {
        $parentId = $attributes['parent_id'] ?? null;

        if ($parentId === null || $category === null) {
            return;
        }

        if ((int) $parentId === (int) $category->getKey()) {
            throw ValidationException::withMessages([
                'parent_id' => 'A category cannot be its own parent.',
            ]);
        }

        $parent = Category::query()->find($parentId);

        if ($parent && $category->hasDescendant($parent)) {
            throw ValidationException::withMessages([
                'parent_id' => 'A category cannot be moved under one of its descendants.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function payload(array $attributes): array
    {
        return Arr::only($attributes, [
            'name_bn',
            'name_en',
            'slug',
            'description',
            'parent_id',
            'status',
            'sort_order',
            'show_in_menu',
            'seo_title',
            'seo_description',
        ]);
    }

    /** @return array<int, string> */
    private function auditKeys(): array
    {
        return [
            'name_bn',
            'name_en',
            'slug',
            'description',
            'parent_id',
            'status',
            'sort_order',
            'show_in_menu',
            'seo_title',
            'seo_description',
        ];
    }
}
