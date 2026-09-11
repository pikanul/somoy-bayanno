<?php

namespace Tests\Feature\Security;

use App\Enums\ArticleStatus;
use App\Enums\ArticleVisibility;
use App\Models\Article;
use App\Models\Author;
use App\Models\MediaAsset;
use App\Models\Tag;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicArticlePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_article_page_renders_published_article_with_seo_and_article_details(): void
    {
        $media = MediaAsset::factory()->create([
            'alt_text' => 'Alt text',
            'caption' => 'Media caption',
            'credit' => 'Media credit',
        ]);
        $author = Author::factory()->create([
            'name_bn' => 'রিপোর্টার নাম',
            'slug' => 'reporter-name',
            'designation' => 'স্টাফ রিপোর্টার',
            'bio_bn' => 'লেখকের পরিচিতি',
        ]);
        $tag = Tag::factory()->create(['name_bn' => 'রাজনীতি']);
        $topic = Topic::factory()->create(['name_bn' => 'নির্বাচন']);

        $article = Article::factory()->published()->create([
            'headline_bn' => 'প্রকাশিত সংবাদ',
            'slug' => 'published-news',
            'subheadline_bn' => 'উপশিরোনাম',
            'summary_bn' => 'মেটা বিবরণ',
            'body_bn' => '<p>মূল সংবাদ</p><script>alert(1)</script><p><a href="https://example.com">বাইরের লিংক</a></p>',
            'featured_media_id' => $media->getKey(),
            'image_caption' => 'ছবির ক্যাপশন',
            'image_credit' => 'ছবির ক্রেডিট',
            'updated_content_at' => now(),
            'correction_note' => 'সংশোধনী নোট',
            'seo_title' => 'এসইও শিরোনাম',
            'seo_description' => 'এসইও বিবরণ',
        ]);
        $article->authors()->attach($author->getKey(), ['sort_order' => 0]);
        $article->tags()->attach($tag->getKey());
        $article->topics()->attach($topic->getKey());

        $response = $this->get(route('articles.show', $article->slug));

        $response
            ->assertOk()
            ->assertSee('প্রকাশিত সংবাদ')
            ->assertSee('উপশিরোনাম')
            ->assertSee('রিপোর্টার নাম')
            ->assertSee('প্রকাশ:')
            ->assertSee('আপডেট:')
            ->assertSee('ছবির ক্যাপশন')
            ->assertSee('ছবি: ছবির ক্রেডিট')
            ->assertSee('মূল সংবাদ')
            ->assertSee('সম্পর্কিত সংবাদ')
            ->assertSee('শেয়ার করুন')
            ->assertSee('সংশোধনী নোট')
            ->assertSee('#রাজনীতি')
            ->assertSee('নির্বাচন')
            ->assertSee('লেখকের পরিচিতি')
            ->assertSee('Advertisement')
            ->assertSee('NewsArticle')
            ->assertSee('BreadcrumbList')
            ->assertSee('<meta name="description" content="এসইও বিবরণ">', false)
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    #[DataProvider('nonPublicArticleStates')]
    public function test_non_public_articles_cannot_be_viewed(string $state): void
    {
        $attributes = match ($state) {
            'draft' => ['status' => ArticleStatus::Draft],
            'pending' => ['status' => ArticleStatus::PendingReview],
            'private' => ['status' => ArticleStatus::Published, 'visibility' => ArticleVisibility::Private],
            'future published' => ['status' => ArticleStatus::Published, 'published_at' => now()->addHour()],
            'scheduled' => ['status' => ArticleStatus::Scheduled, 'published_at' => now()->addHour()],
            'rejected' => ['status' => ArticleStatus::Rejected],
            'unpublished' => ['status' => ArticleStatus::Unpublished],
            'archived' => ['status' => ArticleStatus::Archived],
        };

        $article = Article::factory()->create(array_merge([
            'slug' => fake()->unique()->slug(),
            'published_at' => now()->subMinute(),
            'visibility' => ArticleVisibility::Public,
        ], $attributes));

        $this->get(route('articles.show', $article->slug))->assertNotFound();
    }

    /** @return array<string, array<int, string>> */
    public static function nonPublicArticleStates(): array
    {
        return [
            'draft' => ['draft'],
            'pending' => ['pending'],
            'private' => ['private'],
            'future published' => ['future published'],
            'scheduled' => ['scheduled'],
            'rejected' => ['rejected'],
            'unpublished' => ['unpublished'],
            'archived' => ['archived'],
        ];
    }
}
