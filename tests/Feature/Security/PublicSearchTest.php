<?php

namespace Tests\Feature\Security;

use App\Enums\ArticleStatus;
use App\Enums\ArticleVisibility;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_search_finds_bangla_unicode_headline_and_body(): void
    {
        Article::factory()->published()->create([
            'headline_bn' => 'বাংলাদেশে জলবায়ু উদ্যোগ',
            'body_bn' => 'নদীভাঙন মোকাবিলায় নতুন পরিকল্পনা নেওয়া হয়েছে।',
        ]);

        Article::factory()->published()->create([
            'headline_bn' => 'অন্য সংবাদ',
            'body_bn' => 'অন্য বিষয়',
        ]);

        $this->get(route('search', ['q' => 'নদীভাঙন']))
            ->assertOk()
            ->assertSee('বাংলাদেশে জলবায়ু উদ্যোগ')
            ->assertDontSee('অন্য সংবাদ');
    }

    public function test_public_search_matches_authors_tags_and_topics(): void
    {
        $author = Author::factory()->create(['name_bn' => 'রাফিয়া রহমান']);
        $tag = Tag::factory()->create(['name_bn' => 'নির্বাচন']);
        $topic = Topic::factory()->create(['name_bn' => 'বিশেষ প্রতিবেদন']);

        $authorArticle = Article::factory()->published()->create(['headline_bn' => 'লেখক মিলেছে']);
        $tagArticle = Article::factory()->published()->create(['headline_bn' => 'ট্যাগ মিলেছে']);
        $topicArticle = Article::factory()->published()->create(['headline_bn' => 'টপিক মিলেছে']);

        $authorArticle->authors()->attach($author);
        $tagArticle->tags()->attach($tag);
        $topicArticle->topics()->attach($topic);

        $this->get(route('search', ['q' => 'রাফিয়া']))
            ->assertOk()
            ->assertSee('লেখক মিলেছে');

        $this->get(route('search', ['q' => 'নির্বাচন']))
            ->assertOk()
            ->assertSee('ট্যাগ মিলেছে');

        $this->get(route('search', ['q' => 'বিশেষ প্রতিবেদন']))
            ->assertOk()
            ->assertSee('টপিক মিলেছে');
    }

    public function test_search_only_returns_published_public_articles(): void
    {
        Article::factory()->published()->create([
            'headline_bn' => 'দৃশ্যমান অনুসন্ধান',
            'body_bn' => 'সুরক্ষিত অনুসন্ধান শব্দ',
        ]);

        Article::factory()->create([
            'headline_bn' => 'খসড়া ফাঁস হওয়া যাবে না',
            'body_bn' => 'সুরক্ষিত অনুসন্ধান শব্দ',
            'status' => ArticleStatus::Draft,
            'visibility' => ArticleVisibility::Public,
            'published_at' => null,
        ]);

        Article::factory()->published()->create([
            'headline_bn' => 'প্রাইভেট ফাঁস হওয়া যাবে না',
            'body_bn' => 'সুরক্ষিত অনুসন্ধান শব্দ',
            'visibility' => ArticleVisibility::Private,
        ]);

        Article::factory()->published()->create([
            'headline_bn' => 'ভবিষ্যৎ ফাঁস হওয়া যাবে না',
            'body_bn' => 'সুরক্ষিত অনুসন্ধান শব্দ',
            'published_at' => now()->addDay(),
        ]);

        $this->get(route('search', ['q' => 'সুরক্ষিত অনুসন্ধান শব্দ']))
            ->assertOk()
            ->assertSee('দৃশ্যমান অনুসন্ধান')
            ->assertDontSee('খসড়া ফাঁস হওয়া যাবে না')
            ->assertDontSee('প্রাইভেট ফাঁস হওয়া যাবে না')
            ->assertDontSee('ভবিষ্যৎ ফাঁস হওয়া যাবে না');
    }

    public function test_search_supports_category_author_date_filters_and_latest_sort(): void
    {
        $category = Category::factory()->create(['name_bn' => 'জাতীয়']);
        $otherCategory = Category::factory()->create();
        $author = Author::factory()->create(['name_bn' => 'পরীক্ষক লেখক']);

        $matchingArticle = Article::factory()->published()->create([
            'headline_bn' => 'ফিল্টার মিলেছে',
            'primary_category_id' => $category,
            'published_at' => now()->subDays(2),
        ]);
        $matchingArticle->categories()->attach($category, ['is_primary' => true, 'sort_order' => 0]);
        $matchingArticle->authors()->attach($author);

        $otherArticle = Article::factory()->published()->create([
            'headline_bn' => 'ফিল্টার মিলবে না',
            'primary_category_id' => $otherCategory,
            'published_at' => now()->subDays(2),
        ]);
        $otherArticle->categories()->attach($otherCategory, ['is_primary' => true, 'sort_order' => 0]);

        $this->get(route('search', [
            'q' => 'ফিল্টার',
            'category' => $category->getKey(),
            'author' => $author->getKey(),
            'from' => now()->subDays(3)->toDateString(),
            'to' => now()->subDay()->toDateString(),
            'sort' => 'latest',
        ]))
            ->assertOk()
            ->assertSee('ফিল্টার মিলেছে')
            ->assertDontSee('ফিল্টার মিলবে না');
    }

    public function test_search_validates_query_length_and_filter_values(): void
    {
        $this->get(route('search', ['q' => 'ক']))
            ->assertSessionHasErrors('q');

        $this->get(route('search', ['q' => str_repeat('a', 121)]))
            ->assertSessionHasErrors('q');

        $this->get(route('search', ['sort' => 'random']))
            ->assertSessionHasErrors('sort');
    }

    public function test_search_results_are_paginated(): void
    {
        Article::factory()
            ->count(14)
            ->published()
            ->create(['headline_bn' => 'পেজিনেশন অনুসন্ধান']);

        $this->get(route('search', ['q' => 'পেজিনেশন']))
            ->assertOk()
            ->assertSee('Go to page 2');
    }

    public function test_search_route_is_rate_limited(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->get(route('search', ['q' => 'বাংলা']))->assertOk();
        }

        $this->get(route('search', ['q' => 'বাংলা']))
            ->assertTooManyRequests();
    }
}
