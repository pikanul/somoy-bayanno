<?php

namespace App\Services;

use App\Enums\BreakingNewsStatus;
use App\Enums\BreakingNewsTargetType;
use App\Models\BreakingNews;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BreakingNewsAdministrationService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(mixed $actor, array $attributes): BreakingNews
    {
        Gate::forUser($actor)->authorize('create', BreakingNews::class);

        $attributes = $this->validatedAttributes($attributes);
        $attributes['created_by'] = $actor?->getKey();

        return BreakingNews::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(mixed $actor, BreakingNews $breakingNews, array $attributes): BreakingNews
    {
        Gate::forUser($actor)->authorize('update', $breakingNews);

        $attributes = $this->validatedAttributes($attributes);
        $attributes['updated_by'] = $actor?->getKey();

        $breakingNews->update($attributes);

        return $breakingNews->refresh();
    }

    /**
     * @param  array<int, int>  $orderedIds
     */
    public function reorder(mixed $actor, array $orderedIds): void
    {
        Gate::forUser($actor)->authorize('update', BreakingNews::query()->firstOrNew());

        $orderedIds = array_values(array_map('intval', $orderedIds));
        $existingCount = BreakingNews::query()->whereIn('id', $orderedIds)->count();

        if ($existingCount !== count(array_unique($orderedIds))) {
            throw ValidationException::withMessages([
                'priority' => 'Breaking news ordering contains invalid or duplicate records.',
            ]);
        }

        $priority = count($orderedIds);

        foreach ($orderedIds as $id) {
            BreakingNews::query()->whereKey($id)->update(['priority' => $priority--]);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function validatedAttributes(array $attributes): array
    {
        $validated = Validator::make($attributes, [
            'headline_bn' => ['required', 'string', 'max:255'],
            'target_type' => ['required', Rule::enum(BreakingNewsTargetType::class)],
            'article_id' => ['nullable', 'integer', Rule::exists('articles', 'id')->whereNull('deleted_at')],
            'external_url' => ['nullable', 'url:https', 'max:2048'],
            'priority' => ['required', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'status' => ['required', Rule::enum(BreakingNewsStatus::class)],
        ])->validate();

        $this->validateTarget($validated);

        $validated['headline_bn'] = $this->cleanText((string) $validated['headline_bn']);

        return Arr::only($validated, [
            'headline_bn',
            'target_type',
            'article_id',
            'external_url',
            'priority',
            'starts_at',
            'ends_at',
            'status',
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function validateTarget(array &$attributes): void
    {
        $targetType = BreakingNewsTargetType::from((string) $attributes['target_type']);

        if ($targetType === BreakingNewsTargetType::Article) {
            if (empty($attributes['article_id'])) {
                throw ValidationException::withMessages(['article_id' => 'Choose an article for article breaking news.']);
            }

            $attributes['external_url'] = null;

            return;
        }

        if ($targetType === BreakingNewsTargetType::External) {
            if (empty($attributes['external_url'])) {
                throw ValidationException::withMessages(['external_url' => 'Enter an external URL for external breaking news.']);
            }

            $this->validateExternalUrl((string) $attributes['external_url']);
            $attributes['article_id'] = null;

            return;
        }

        $attributes['article_id'] = null;
        $attributes['external_url'] = null;
    }

    private function validateExternalUrl(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($scheme !== 'https' || $host === '' || in_array($scheme, ['javascript', 'data', 'vbscript'], true)) {
            throw ValidationException::withMessages(['external_url' => 'Only HTTPS external URLs are allowed.']);
        }

        if (str_contains($url, '<')) {
            throw ValidationException::withMessages(['external_url' => 'External URLs cannot contain HTML.']);
        }
    }

    private function cleanText(string $value): string
    {
        $value = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $value) ?? '';

        return trim(strip_tags($value));
    }
}
