<?php

namespace App\Services;

use App\Models\Article;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PublicArchiveService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Article>
     */
    public function articles(array $filters): LengthAwarePaginator
    {
        $query = Article::query()
            ->publiclyVisible()
            ->with(['primaryCategory', 'featuredMedia', 'authors']);

        if (($filters['start'] ?? null) instanceof CarbonImmutable && ($filters['end'] ?? null) instanceof CarbonImmutable) {
            $query->whereBetween('published_at', [$filters['start'], $filters['end']]);
        }

        if (! empty($filters['category'])) {
            $query->whereHas('categories', fn (Builder $categoryQuery): Builder => $categoryQuery->whereKey((int) $filters['category']));
        }

        if (! empty($filters['author'])) {
            $query->whereHas('authors', fn (Builder $authorQuery): Builder => $authorQuery->whereKey((int) $filters['author']));
        }

        return $query
            ->latest('published_at')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();
    }

    /** @return array{start: CarbonImmutable, end: CarbonImmutable, title: string, label: string} */
    public function yearWindow(int $year): array
    {
        $start = CarbonImmutable::create($year, 1, 1)->startOfDay();

        return [
            'start' => $start,
            'end' => $start->endOfYear(),
            'title' => $year.' সালের সংবাদ',
            'label' => (string) $year,
        ];
    }

    /** @return array{start: CarbonImmutable, end: CarbonImmutable, title: string, label: string} */
    public function monthWindow(int $year, int $month): array
    {
        $start = CarbonImmutable::create($year, $month, 1)->startOfDay();

        return [
            'start' => $start,
            'end' => $start->endOfMonth(),
            'title' => $start->translatedFormat('F Y').' সংবাদ',
            'label' => $start->format('Y/m'),
        ];
    }

    /** @return array{start: CarbonImmutable, end: CarbonImmutable, title: string, label: string} */
    public function dateWindow(int $year, int $month, int $day): array
    {
        $start = CarbonImmutable::create($year, $month, $day)->startOfDay();

        return [
            'start' => $start,
            'end' => $start->endOfDay(),
            'title' => $start->translatedFormat('d F Y').' সংবাদ',
            'label' => $start->toDateString(),
        ];
    }
}
