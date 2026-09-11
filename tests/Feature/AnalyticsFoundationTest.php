<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleMetric;
use App\Services\ArticleMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AnalyticsFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_views_are_buffered_in_cache_and_flushed_to_hourly_aggregate(): void
    {
        config(['analytics.article_metrics.flush_threshold' => 3]);

        $article = Article::factory()->published()->create();

        $this->get(route('articles.show', $article->slug))->assertOk();
        $this->get(route('articles.show', $article->slug))->assertOk();

        $this->assertDatabaseCount('article_metrics', 0);

        $this->get(route('articles.show', $article->slug))->assertOk();

        $this->assertDatabaseHas('article_metrics', [
            'article_id' => $article->getKey(),
            'bucket_started_at' => now()->startOfHour()->toDateTimeString(),
            'views' => 3,
        ]);
    }

    public function test_manual_flush_persists_pending_article_view_counters(): void
    {
        config(['analytics.article_metrics.flush_threshold' => 99]);

        $article = Article::factory()->published()->create();

        app(ArticleMetricsService::class)->trackView($article);
        app(ArticleMetricsService::class)->trackView($article);

        $this->assertSame(2, app(ArticleMetricsService::class)->flush());
        $this->assertDatabaseHas('article_metrics', [
            'article_id' => $article->getKey(),
            'views' => 2,
        ]);
    }

    public function test_most_read_uses_rolling_time_windows_and_public_articles_only(): void
    {
        $popular = Article::factory()->published()->create(['headline_bn' => 'Popular story']);
        $older = Article::factory()->published()->create(['headline_bn' => 'Older story']);
        $draft = Article::factory()->create(['headline_bn' => 'Draft story']);

        ArticleMetric::query()->create([
            'article_id' => $popular->getKey(),
            'bucket_started_at' => now()->subHours(2)->startOfHour(),
            'views' => 10,
        ]);
        ArticleMetric::query()->create([
            'article_id' => $older->getKey(),
            'bucket_started_at' => now()->subDays(10)->startOfHour(),
            'views' => 99,
        ]);
        ArticleMetric::query()->create([
            'article_id' => $draft->getKey(),
            'bucket_started_at' => now()->subHours(2)->startOfHour(),
            'views' => 100,
        ]);

        $mostRead = app(ArticleMetricsService::class)->mostRead(ArticleMetricsService::WindowSevenDays);

        $this->assertTrue($mostRead->first()->is($popular));
        $this->assertFalse($mostRead->contains(fn (Article $article): bool => $article->is($older)));
        $this->assertFalse($mostRead->contains(fn (Article $article): bool => $article->is($draft)));
    }

    public function test_homepage_renders_metric_backed_most_read_stories(): void
    {
        $popular = Article::factory()->published()->create(['headline_bn' => 'Metric-backed story']);
        $latest = Article::factory()->published()->create(['headline_bn' => 'Latest fallback story']);

        ArticleMetric::query()->create([
            'article_id' => $popular->getKey(),
            'bucket_started_at' => now()->subHours(2)->startOfHour(),
            'views' => 10,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Metric-backed story')
            ->assertSee('Latest fallback story');
    }

    public function test_public_layout_outputs_configured_analytics_without_personal_identifiers(): void
    {
        config([
            'analytics.google_analytics_measurement_id' => 'G-TEST123',
            'analytics.google_search_console_verification' => 'search-console-token',
            'analytics.cloudflare_analytics_token' => 'cloudflare-token',
        ]);

        $response = $this->get(route('home'))->assertOk();

        $response->assertSee('G-TEST123');
        $response->assertSee('google-site-verification');
        $response->assertSee('search-console-token');
        $response->assertSee('static.cloudflareinsights.com/beacon.min.js');
        $response->assertSee('anonymize_ip');
        $response->assertDontSee('user_id');
        $response->assertDontSee('REMOTE_ADDR');
    }

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }
}
