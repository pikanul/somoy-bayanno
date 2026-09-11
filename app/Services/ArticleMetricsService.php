<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleMetric;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ArticleMetricsService
{
    public const WindowOneHour = '1h';

    public const WindowTwentyFourHours = '24h';

    public const WindowSevenDays = '7d';

    public const WindowThirtyDays = '30d';

    /** @return array<int, string> */
    public function supportedWindows(): array
    {
        return [
            self::WindowOneHour,
            self::WindowTwentyFourHours,
            self::WindowSevenDays,
            self::WindowThirtyDays,
        ];
    }

    public function trackView(Article $article): void
    {
        if (! $this->isEnabled() || ! $article->exists || ! $article->isPubliclyVisible()) {
            return;
        }

        if (! Schema::hasTable('article_metrics')) {
            return;
        }

        $bucketStartedAt = now()->startOfHour();
        $pendingKey = $this->pendingViewKey($article, $bucketStartedAt);
        $pendingCount = (int) Cache::increment($pendingKey);

        Cache::put($pendingKey, $pendingCount, now()->addHours(2));
        $this->rememberPendingViewKey($pendingKey);

        if ($pendingCount < $this->flushThreshold()) {
            return;
        }

        $this->flushPendingKey($pendingKey);
    }

    /**
     * @return Collection<int, Article>
     */
    public function mostRead(string $window = self::WindowTwentyFourHours, int $limit = 5): Collection
    {
        $window = in_array($window, $this->supportedWindows(), true) ? $window : self::WindowTwentyFourHours;
        $limit = max(1, min($limit, 20));

        $cacheKey = $this->mostReadCacheKey($window, $limit);
        $cachedArticles = Cache::get($cacheKey);

        if ($cachedArticles instanceof Collection) {
            return $cachedArticles;
        }

        $articles = $this->resolveMostRead($window, $limit);

        Cache::put($cacheKey, $articles, $this->mostReadCacheSeconds());

        return $articles;
    }

    public function flush(): int
    {
        $flushed = 0;

        foreach ($this->pendingViewKeys() as $pendingKey) {
            $flushed += $this->flushPendingKey($pendingKey);
        }

        return $flushed;
    }

    private function incrementBucket(Article $article, Carbon $bucketStartedAt): void
    {
        $this->incrementBucketBy($article->getKey(), $bucketStartedAt->toDateTimeString(), 1);
    }

    /**
     * @return Collection<int, Article>
     */
    private function resolveMostRead(string $window, int $limit): Collection
    {
        if (! Schema::hasTable('article_metrics')) {
            return collect();
        }

        $views = ArticleMetric::query()
            ->select('article_id', DB::raw('SUM(views) as aggregate_views'))
            ->where('bucket_started_at', '>=', $this->windowStart($window))
            ->groupBy('article_id')
            ->orderByDesc('aggregate_views')
            ->limit($limit)
            ->pluck('aggregate_views', 'article_id');

        if ($views->isEmpty()) {
            return collect();
        }

        return Article::query()
            ->with(['primaryCategory', 'featuredMedia'])
            ->publiclyVisible()
            ->whereKey($views->keys())
            ->get()
            ->sortBy(fn (Article $article): int => -1 * (int) $views->get($article->getKey(), 0))
            ->values();
    }

    private function incrementBucketBy(int $articleId, string $bucket, int $views): void
    {
        ArticleMetric::query()->upsert([
            [
                'article_id' => $articleId,
                'bucket_started_at' => $bucket,
                'views' => $views,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['article_id', 'bucket_started_at'], [
            'views' => DB::raw('views + '.$views),
            'updated_at' => now(),
        ]);
    }

    private function flushPendingKey(string $pendingKey): int
    {
        $parts = explode(':', $pendingKey);
        $articleId = (int) ($parts[1] ?? 0);
        $bucket = $parts[2] ?? null;

        if ($articleId < 1 || blank($bucket)) {
            Cache::forget($pendingKey);

            return 0;
        }

        $views = (int) Cache::pull($pendingKey, 0);

        if ($views < 1) {
            return 0;
        }

        $this->incrementBucketBy($articleId, Carbon::createFromFormat('YmdH', $bucket)->toDateTimeString(), $views);
        $this->forgetMostReadCaches();

        return $views;
    }

    private function rememberPendingViewKey(string $pendingKey): void
    {
        $keys = $this->pendingViewKeys();

        if (in_array($pendingKey, $keys, true)) {
            return;
        }

        $keys[] = $pendingKey;

        Cache::put($this->pendingViewKeysKey(), $keys, now()->addDays(2));
    }

    /** @return array<int, string> */
    private function pendingViewKeys(): array
    {
        $keys = Cache::get($this->pendingViewKeysKey(), []);

        return is_array($keys) ? array_values(array_filter($keys, is_string(...))) : [];
    }

    private function pendingViewKeysKey(): string
    {
        return 'article-metrics:pending-keys';
    }

    private function pendingViewKey(Article $article, Carbon $bucketStartedAt): string
    {
        return 'article-metrics:'.$article->getKey().':'.$bucketStartedAt->format('YmdH');
    }

    private function forgetMostReadCaches(): void
    {
        foreach ($this->supportedWindows() as $window) {
            Cache::forget($this->mostReadCacheKey($window));
        }
    }

    private function windowStart(string $window): Carbon
    {
        return match ($window) {
            self::WindowOneHour => now()->subHour()->startOfHour(),
            self::WindowSevenDays => now()->subDays(7)->startOfHour(),
            self::WindowThirtyDays => now()->subDays(30)->startOfHour(),
            default => now()->subDay()->startOfHour(),
        };
    }

    private function isEnabled(): bool
    {
        return (bool) config('analytics.article_metrics.enabled', true);
    }

    private function flushThreshold(): int
    {
        return (int) config('analytics.article_metrics.flush_threshold', 25);
    }

    private function mostReadCacheSeconds(): int
    {
        return (int) config('analytics.article_metrics.most_read_cache_seconds', 300);
    }

    private function mostReadCacheKey(string $window, int $limit = 5): string
    {
        return "article-metrics:most-read:{$window}:{$limit}";
    }
}
