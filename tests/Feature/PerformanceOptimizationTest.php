<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\MediaAsset;
use App\Services\PublicContentCache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_homepage_uses_cache_after_first_render(): void
    {
        $this->seedPublicArticles();

        $coldQueries = $this->countQueries(fn () => $this->get(route('home'))->assertOk());
        $warmQueries = $this->countQueries(fn () => $this->get(route('home'))->assertOk());

        $this->assertGreaterThan(0, $coldQueries);
        $this->assertLessThanOrEqual(5, $warmQueries);
        $this->assertLessThan($coldQueries, $warmQueries);
    }

    public function test_public_article_show_avoids_repeated_relationship_queries_after_cache_warms(): void
    {
        $article = $this->seedPublicArticles()->firstOrFail();

        $coldQueries = $this->countQueries(fn () => $this->get(route('articles.show', $article->slug))->assertOk());
        $warmQueries = $this->countQueries(fn () => $this->get(route('articles.show', $article->slug))->assertOk());

        $this->assertGreaterThan(0, $coldQueries);
        $this->assertLessThanOrEqual(5, $warmQueries);
        $this->assertLessThan($coldQueries, $warmQueries);
    }

    public function test_article_changes_invalidate_public_cache_namespace(): void
    {
        $article = $this->seedPublicArticles()->firstOrFail();
        $cache = app(PublicContentCache::class);

        $this->get(route('articles.show', $article->slug))->assertOk();
        $versionBeforeChange = $cache->version();

        $article->update(['headline_bn' => 'পরিবর্তিত শিরোনাম']);

        $this->assertGreaterThan($versionBeforeChange, $cache->version());
    }

    /** @return Collection<int, Article> */
    private function seedPublicArticles(): Collection
    {
        Cache::flush();

        $categories = Category::factory()
            ->count(3)
            ->sequence(
                ['name_bn' => 'জাতীয়', 'slug' => 'national', 'sort_order' => 1],
                ['name_bn' => 'রাজনীতি', 'slug' => 'politics', 'sort_order' => 2],
                ['name_bn' => 'খেলা', 'slug' => 'sports', 'sort_order' => 3],
            )
            ->create();

        $authors = Author::factory()->count(2)->create();
        $media = MediaAsset::factory()->create();

        return Article::factory()
            ->count(12)
            ->published()
            ->sequence(fn (Sequence $sequence): array => [
                'primary_category_id' => $categories[$sequence->index % $categories->count()]->getKey(),
                'featured_media_id' => $media->getKey(),
                'is_featured' => $sequence->index === 0,
                'published_at' => now()->subMinutes($sequence->index + 1),
            ])
            ->create()
            ->each(function (Article $article) use ($authors): void {
                $article->categories()->sync([$article->primary_category_id => ['is_primary' => true, 'sort_order' => 0]]);
                $article->authors()->sync($authors->pluck('id')->all());
            });
    }

    private function countQueries(callable $callback): int
    {
        $queries = 0;

        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $callback();

        return $queries;
    }
}
