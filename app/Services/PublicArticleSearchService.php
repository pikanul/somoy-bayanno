<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PublicArticleSearchService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Article>
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $term = trim((string) ($filters['q'] ?? ''));

        $query = Article::query()
            ->publiclyVisible()
            ->with(['primaryCategory', 'featuredMedia', 'authors', 'tags', 'topics']);

        if ($term !== '') {
            $this->applyTerm($query, $term);
        }

        if (! empty($filters['category'])) {
            $query->whereHas('categories', fn (Builder $categoryQuery): Builder => $categoryQuery->whereKey((int) $filters['category']));
        }

        if (! empty($filters['author'])) {
            $query->whereHas('authors', fn (Builder $authorQuery): Builder => $authorQuery->whereKey((int) $filters['author']));
        }

        if (! empty($filters['from'])) {
            $query->whereDate('published_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('published_at', '<=', $filters['to']);
        }

        if (($filters['sort'] ?? 'relevance') === 'latest' || $term === '') {
            $query->latest('published_at');
        } else {
            $this->applyRelevanceSort($query, $term);
        }

        return $query
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();
    }

    /** @param Builder<Article> $query */
    private function applyTerm(Builder $query, string $term): void
    {
        $like = $this->like($term);

        $query->where(function (Builder $searchQuery) use ($like): void {
            $searchQuery
                ->where('headline_bn', 'like', $like)
                ->orWhere('headline_en', 'like', $like)
                ->orWhere('subheadline_bn', 'like', $like)
                ->orWhere('summary_bn', 'like', $like)
                ->orWhere('body_bn', 'like', $like)
                ->orWhere('reporter_name', 'like', $like)
                ->orWhereHas('authors', function (Builder $authorQuery) use ($like): void {
                    $authorQuery
                        ->where('name_bn', 'like', $like)
                        ->orWhere('name_en', 'like', $like);
                })
                ->orWhereHas('tags', function (Builder $tagQuery) use ($like): void {
                    $tagQuery
                        ->where('name_bn', 'like', $like)
                        ->orWhere('name_en', 'like', $like);
                })
                ->orWhereHas('topics', function (Builder $topicQuery) use ($like): void {
                    $topicQuery
                        ->where('name_bn', 'like', $like)
                        ->orWhere('name_en', 'like', $like);
                });
        });
    }

    /** @param Builder<Article> $query */
    private function applyRelevanceSort(Builder $query, string $term): void
    {
        $escaped = addcslashes($term, "\\'%_");

        $query->orderByRaw(
            'CASE
                WHEN headline_bn LIKE ? THEN 50
                WHEN subheadline_bn LIKE ? THEN 35
                WHEN summary_bn LIKE ? THEN 25
                WHEN reporter_name LIKE ? THEN 15
                WHEN body_bn LIKE ? THEN 5
                ELSE 0
            END DESC',
            [
                '%'.$escaped.'%',
                '%'.$escaped.'%',
                '%'.$escaped.'%',
                '%'.$escaped.'%',
                '%'.$escaped.'%',
            ],
        )->latest('published_at');
    }

    private function like(string $term): string
    {
        return '%'.Str::of($term)->replace(['\\', '%', '_'], ['\\\\', '\%', '\_'])->toString().'%';
    }
}
