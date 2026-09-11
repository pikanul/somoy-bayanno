<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Enums\ArticleVisibility;
use App\Models\Article;
use App\Models\Author;
use App\Models\MediaAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SeoFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_metadata_uses_real_values_and_structured_data_fallbacks(): void
    {
        $author = Author::factory()->create([
            'name_bn' => 'আসল লেখক',
            'slug' => 'real-author',
        ]);
        $media = MediaAsset::factory()->create([
            'external_url' => 'https://cdn.example.test/story.jpg',
            'path' => null,
            'storage_disk' => null,
        ]);
        $article = Article::factory()->published()->create([
            'type' => ArticleType::Opinion,
            'headline_bn' => 'বাস্তব শিরোনাম',
            'slug' => 'real-headline',
            'summary_bn' => null,
            'seo_title' => null,
            'seo_description' => null,
            'social_title' => 'সামাজিক শিরোনাম',
            'social_description' => null,
            'body_bn' => '<p>বাস্তব প্রতিবেদন থেকে তৈরি বিবরণ।</p>',
            'featured_media_id' => $media->getKey(),
        ]);
        $article->authors()->attach($author->getKey(), ['sort_order' => 0]);

        $response = $this->get(route('articles.show', $article->slug));

        $response
            ->assertOk()
            ->assertSee('<title>সামাজিক শিরোনাম - '.config('app.name').'</title>', false)
            ->assertSee('<meta name="description" content="বাস্তব প্রতিবেদন থেকে তৈরি বিবরণ।">', false)
            ->assertSee('<link rel="canonical" href="'.route('articles.show', $article->slug).'">', false)
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('<meta property="og:image" content="https://cdn.example.test/story.jpg">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('"@type":"Article"', false)
            ->assertSee('"@type":"Person","name":"আসল লেখক"', false)
            ->assertSee('"@type":"Organization"', false)
            ->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_homepage_and_author_pages_render_canonical_metadata_and_structured_data(): void
    {
        $author = Author::factory()->create([
            'name_bn' => 'লেখক পাতা',
            'slug' => 'author-page',
            'bio_bn' => 'লেখকের বাস্তব পরিচিতি',
            'seo_title' => null,
            'seo_description' => null,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('home').'">', false)
            ->assertSee('"@type":"Organization"', false);

        $this->get(route('authors.show', $author->slug))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('authors.show', $author->slug).'">', false)
            ->assertSee('<meta property="og:type" content="profile">', false)
            ->assertSee('<meta name="description" content="লেখকের বাস্তব পরিচিতি">', false)
            ->assertSee('"@type":"Person"', false)
            ->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_sitemap_includes_only_public_canonical_urls(): void
    {
        $included = Article::factory()->published()->create([
            'headline_bn' => 'সাইটম্যাপ সংবাদ',
            'slug' => 'sitemap-news',
            'canonical_url' => 'https://external.example.test/not-used',
        ]);
        Article::factory()->published()->create([
            'headline_bn' => 'আপডেটেড সংবাদ',
            'slug' => 'updated-news',
            'status' => ArticleStatus::Updated,
        ]);
        Article::factory()->create([
            'headline_bn' => 'খসড়া সংবাদ',
            'slug' => 'draft-news',
            'status' => ArticleStatus::Draft,
            'published_at' => now()->subDay(),
        ]);
        Article::factory()->published()->create([
            'headline_bn' => 'ব্যক্তিগত সংবাদ',
            'slug' => 'private-news',
            'visibility' => ArticleVisibility::Private,
        ]);
        Article::factory()->create([
            'headline_bn' => 'ভবিষ্যৎ সংবাদ',
            'slug' => 'future-news',
            'status' => ArticleStatus::Published,
            'visibility' => ArticleVisibility::Public,
            'published_at' => now()->addDay(),
        ]);
        Article::factory()->create([
            'headline_bn' => 'সূচিভুক্ত নয়',
            'slug' => 'scheduled-news',
            'status' => ArticleStatus::Scheduled,
            'visibility' => ArticleVisibility::Public,
            'published_at' => now()->addDay(),
            'scheduled_at' => now()->addDay(),
        ]);

        $response = $this->get(route('sitemap'));

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('home'), false)
            ->assertSee(route('articles.show', $included->slug), false)
            ->assertSee(route('articles.show', 'updated-news'), false)
            ->assertDontSee('https://external.example.test/not-used')
            ->assertDontSee('draft-news')
            ->assertDontSee('private-news')
            ->assertDontSee('future-news')
            ->assertDontSee('scheduled-news');
    }

    #[DataProvider('emptySpecializedSitemapRoutes')]
    public function test_specialized_sitemap_architecture_is_available_without_fake_data(string $routeName): void
    {
        Article::factory()->published()->create(['slug' => 'published-story']);

        $this->get(route($routeName))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertDontSee('published-story');
    }

    public function test_robots_txt_points_to_public_sitemap_and_blocks_admin_area(): void
    {
        $this->get(route('robots'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Disallow: /newsroom', false)
            ->assertSee('Sitemap: '.url('/sitemap.xml'), false);
    }

    /** @return array<string, array<int, string>> */
    public static function emptySpecializedSitemapRoutes(): array
    {
        return [
            'news sitemap' => ['sitemap.news'],
            'image sitemap' => ['sitemap.image'],
            'video sitemap' => ['sitemap.video'],
        ];
    }
}
