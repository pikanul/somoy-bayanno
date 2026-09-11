<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Gallery;
use App\Models\HomepageSection;
use App\Models\Video;
use App\Services\ArticleMetricsService;
use App\Services\PublicContentCache;
use App\Services\SeoMetadataService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PublicHomeController extends Controller
{
    public function __invoke(SeoMetadataService $seo, PublicContentCache $publicCache, ArticleMetricsService $metrics): View
    {
        $payload = Cache::get($publicCache->homepageKey());

        if (! is_array($payload)) {
            $payload = $this->homepagePayload();

            Cache::put($publicCache->homepageKey(), $payload, $publicCache->ttl());
        }

        $payload = $this->normalizeHomepagePayload($payload);
        $payload['mostReadArticles'] = $metrics->mostRead(limit: 5);
        $payload['seo'] = $seo->home();
        $payload['jsonLd'] = [$seo->organizationJsonLd()];

        return view('public.home', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeHomepagePayload(array $payload): array
    {
        foreach (['secondaryStories', 'latestArticles', 'categorySections', 'videos', 'galleries', 'managedSections'] as $key) {
            if (! ($payload[$key] ?? null) instanceof Collection) {
                $payload[$key] = collect();
            }
        }

        $payload['leadArticle'] = $payload['leadArticle'] instanceof Article ? $payload['leadArticle'] : null;

        return $payload;
    }

    /** @return array<string, mixed> */
    private function homepagePayload(): array
    {
        if (! Schema::hasTable('articles')) {
            return [
                'leadArticle' => null,
                'secondaryStories' => collect(),
                'latestArticles' => collect(),
                'categorySections' => collect(),
                'videos' => collect(),
                'galleries' => collect(),
                'managedSections' => collect(),
            ];
        }

        $baseQuery = Article::query()
            ->with(['primaryCategory', 'featuredMedia'])
            ->publiclyVisible();

        $leadArticle = (clone $baseQuery)
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->first();

        $secondaryStories = (clone $baseQuery)
            ->when($leadArticle, fn (Builder $query): Builder => $query->whereKeyNot($leadArticle->getKey()))
            ->latest('published_at')
            ->limit(4)
            ->get();

        $latestArticles = (clone $baseQuery)
            ->latest('published_at')
            ->limit(10)
            ->get();

        return [
            'leadArticle' => $leadArticle,
            'secondaryStories' => $secondaryStories,
            'latestArticles' => $latestArticles,
            'categorySections' => $this->categorySections(),
            'videos' => $this->videos(),
            'galleries' => $this->galleries(),
            'managedSections' => $this->managedSections(),
        ];
    }

    /** @return Collection<string, HomepageSection> */
    private function managedSections(): Collection
    {
        if (! Schema::hasTable('homepage_sections') || ! Schema::hasTable('homepage_items')) {
            return collect();
        }

        return HomepageSection::query()
            ->with(['items' => function ($query): void {
                $query
                    ->currentlyVisible()
                    ->with(['article.primaryCategory', 'article.featuredMedia'])
                    ->whereHas('article', fn (Builder $query) => $query->publiclyVisible())
                    ->orderBy('sort_order');
            }])
            ->where('status', 'published')
            ->orderBy('sort_order')
            ->get()
            ->keyBy(fn (HomepageSection $section): string => $section->key->value);
    }

    /** @return Collection<int, array{key: string, title: string, articles: Collection<int, Article>}> */
    private function categorySections(): Collection
    {
        $sections = collect([
            ['key' => 'national', 'title' => 'জাতীয়', 'matches' => ['national', 'জাতীয়']],
            ['key' => 'politics', 'title' => 'রাজনীতি', 'matches' => ['politics', 'political', 'রাজনীতি']],
            ['key' => 'international', 'title' => 'আন্তর্জাতিক', 'matches' => ['international', 'world', 'বিশ্ব', 'আন্তর্জাতিক']],
            ['key' => 'business', 'title' => 'অর্থনীতি', 'matches' => ['business', 'economy', 'অর্থনীতি', 'ব্যবসা']],
            ['key' => 'sports', 'title' => 'খেলা', 'matches' => ['sports', 'খেলা']],
            ['key' => 'entertainment', 'title' => 'বিনোদন', 'matches' => ['entertainment', 'বিনোদন']],
            ['key' => 'technology', 'title' => 'প্রযুক্তি', 'matches' => ['technology', 'tech', 'প্রযুক্তি']],
            ['key' => 'lifestyle', 'title' => 'লাইফস্টাইল', 'matches' => ['lifestyle', 'life', 'লাইফস্টাইল']],
            ['key' => 'opinion', 'title' => 'মতামত', 'matches' => ['opinion', 'editorial', 'মতামত', 'সম্পাদকীয়']],
        ]);

        return $sections->map(function (array $section): array {
            return [
                'key' => $section['key'],
                'title' => $section['title'],
                'articles' => $this->articlesForCategory($section['matches']),
            ];
        });
    }

    /**
     * @param  array<int, string>  $matches
     * @return Collection<int, Article>
     */
    private function articlesForCategory(array $matches): Collection
    {
        return Article::query()
            ->with(['primaryCategory', 'featuredMedia'])
            ->publiclyVisible()
            ->whereHas('primaryCategory', function (Builder $query) use ($matches): void {
                $query->where(function (Builder $query) use ($matches): void {
                    foreach ($matches as $match) {
                        $query
                            ->orWhere('slug', 'like', "%{$match}%")
                            ->orWhere('name_en', 'like', "%{$match}%")
                            ->orWhere('name_bn', 'like', "%{$match}%");
                    }
                });
            })
            ->latest('published_at')
            ->limit(4)
            ->get();
    }

    /** @return Collection<int, Video> */
    private function videos(): Collection
    {
        if (! Schema::hasTable('videos')) {
            return collect();
        }

        return Video::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->limit(4)
            ->get();
    }

    /** @return Collection<int, Gallery> */
    private function galleries(): Collection
    {
        if (! Schema::hasTable('galleries')) {
            return collect();
        }

        return Gallery::query()
            ->with(['coverImage'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->limit(4)
            ->get();
    }
}
