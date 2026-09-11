<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Author;
use App\Models\BreakingNews;
use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PublicContentCache
{
    public const VersionKey = 'public:content:version';

    public function version(): int
    {
        $version = Cache::get(self::VersionKey);

        if (! is_int($version)) {
            Cache::forever(self::VersionKey, 1);

            return 1;
        }

        return $version;
    }

    public function flushPublicContent(): void
    {
        Cache::forever(self::VersionKey, $this->version() + 1);
    }

    public function forgetArticle(Article $article): void
    {
        Cache::forget($this->articleKey((string) $article->slug));

        $originalSlug = $article->getOriginal('slug');

        if (is_string($originalSlug) && $originalSlug !== $article->slug) {
            Cache::forget($this->articleKey($originalSlug));
        }
    }

    /** @return array<int, array{label: string, url: string}> */
    public function navigationItems(): array
    {
        return Cache::remember($this->key('navigation'), $this->ttl(), function (): array {
            if (! Schema::hasTable('categories')) {
                return $this->fallbackNavigationItems();
            }

            $categories = Category::query()
                ->where('status', 'active')
                ->where('show_in_menu', true)
                ->orderBy('sort_order')
                ->orderBy('name_bn')
                ->limit(12)
                ->get(['name_bn', 'slug']);

            if ($categories->isEmpty()) {
                return $this->fallbackNavigationItems();
            }

            return $categories
                ->map(fn (Category $category): array => [
                    'label' => $category->name_bn,
                    'url' => route('home', ['category' => $category->slug]),
                ])
                ->prepend(['label' => 'সর্বশেষ', 'url' => route('home')])
                ->values()
                ->all();
        });
    }

    /** @return Collection<int, BreakingNews> */
    public function breakingNews(): Collection
    {
        $cacheKey = $this->key('breaking-news');
        $items = Cache::get($cacheKey);

        if ($items instanceof Collection) {
            return $items;
        }

        if (! Schema::hasTable('breaking_news')) {
            return collect();
        }

        $items = BreakingNews::query()
            ->with(['article:id,slug'])
            ->currentlyActive()
            ->limit(8)
            ->get(['id', 'headline_bn', 'target_type', 'article_id', 'external_url']);

        Cache::put($cacheKey, $items, $this->ttl());

        return $items;
    }

    /** @return array{categories: Collection<int, Category>, authors: Collection<int, Author>} */
    public function publicFilterOptions(): array
    {
        return Cache::remember($this->key('filter-options'), $this->ttl(), fn (): array => [
            'categories' => Category::query()
                ->orderBy('sort_order')
                ->orderBy('name_bn')
                ->get(['id', 'name_bn']),
            'authors' => Author::query()
                ->orderBy('name_bn')
                ->get(['id', 'name_bn']),
        ]);
    }

    public function homepageKey(): string
    {
        return $this->key('homepage');
    }

    public function articleKey(string $slug): string
    {
        return $this->key('article:'.$slug);
    }

    public function ttl(): int
    {
        return max(60, (int) env('PUBLIC_CACHE_TTL_SECONDS', 300));
    }

    private function key(string $name): string
    {
        return 'public:v'.$this->version().':'.$name;
    }

    /** @return array<int, array{label: string, url: string}> */
    private function fallbackNavigationItems(): array
    {
        return [
            ['label' => 'সর্বশেষ', 'url' => route('home')],
            ['label' => 'জাতীয়', 'url' => '#'],
            ['label' => 'রাজনীতি', 'url' => '#'],
            ['label' => 'অর্থনীতি', 'url' => '#'],
            ['label' => 'বিশ্ব', 'url' => '#'],
            ['label' => 'খেলা', 'url' => '#'],
            ['label' => 'বিনোদন', 'url' => '#'],
            ['label' => 'প্রযুক্তি', 'url' => '#'],
            ['label' => 'ভিডিও', 'url' => '#'],
        ];
    }
}
