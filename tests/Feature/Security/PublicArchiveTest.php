<?php

namespace Tests\Feature\Security;

use App\Enums\ArticleStatus;
use App\Enums\ArticleVisibility;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_archive_index_lists_only_public_published_articles(): void
    {
        Article::factory()->published()->create(['headline_bn' => 'প্রকাশিত আর্কাইভ সংবাদ']);

        Article::factory()->create([
            'headline_bn' => 'খসড়া আর্কাইভে আসবে না',
            'status' => ArticleStatus::Draft,
            'visibility' => ArticleVisibility::Public,
            'published_at' => null,
        ]);

        Article::factory()->published()->create([
            'headline_bn' => 'প্রাইভেট আর্কাইভে আসবে না',
            'visibility' => ArticleVisibility::Private,
        ]);

        Article::factory()->published()->create([
            'headline_bn' => 'ভবিষ্যৎ আর্কাইভে আসবে না',
            'published_at' => now()->addDay(),
        ]);

        $this->get(route('archive.index'))
            ->assertOk()
            ->assertSee('প্রকাশিত আর্কাইভ সংবাদ')
            ->assertDontSee('খসড়া আর্কাইভে আসবে না')
            ->assertDontSee('প্রাইভেট আর্কাইভে আসবে না')
            ->assertDontSee('ভবিষ্যৎ আর্কাইভে আসবে না');
    }

    public function test_archive_can_browse_by_year_month_and_date(): void
    {
        Article::factory()->published()->create([
            'headline_bn' => 'এগারো সেপ্টেম্বর সংবাদ',
            'published_at' => '2026-09-11 10:00:00',
        ]);

        Article::factory()->published()->create([
            'headline_bn' => 'অন্য মাসের সংবাদ',
            'published_at' => '2026-08-11 10:00:00',
        ]);

        Article::factory()->published()->create([
            'headline_bn' => 'অন্য বছরের সংবাদ',
            'published_at' => '2025-09-11 10:00:00',
        ]);

        $this->get(route('archive.year', ['year' => 2026]))
            ->assertOk()
            ->assertSee('এগারো সেপ্টেম্বর সংবাদ')
            ->assertSee('অন্য মাসের সংবাদ')
            ->assertDontSee('অন্য বছরের সংবাদ');

        $this->get(route('archive.month', ['year' => 2026, 'month' => 9]))
            ->assertOk()
            ->assertSee('এগারো সেপ্টেম্বর সংবাদ')
            ->assertDontSee('অন্য মাসের সংবাদ')
            ->assertDontSee('অন্য বছরের সংবাদ');

        $this->get(route('archive.date', ['year' => 2026, 'month' => 9, 'day' => 11]))
            ->assertOk()
            ->assertSee('এগারো সেপ্টেম্বর সংবাদ')
            ->assertDontSee('অন্য মাসের সংবাদ')
            ->assertDontSee('অন্য বছরের সংবাদ');
    }

    public function test_archive_supports_category_and_author_filters(): void
    {
        $category = Category::factory()->create(['name_bn' => 'জাতীয়']);
        $otherCategory = Category::factory()->create(['name_bn' => 'খেলা']);
        $author = Author::factory()->create(['name_bn' => 'আর্কাইভ লেখক']);

        $matching = Article::factory()->published()->create([
            'headline_bn' => 'ফিল্টার করা আর্কাইভ',
            'primary_category_id' => $category,
            'published_at' => '2026-09-11 10:00:00',
        ]);
        $matching->categories()->attach($category, ['is_primary' => true, 'sort_order' => 0]);
        $matching->authors()->attach($author);

        $other = Article::factory()->published()->create([
            'headline_bn' => 'ফিল্টার ছাড়া আর্কাইভ',
            'primary_category_id' => $otherCategory,
            'published_at' => '2026-09-11 10:00:00',
        ]);
        $other->categories()->attach($otherCategory, ['is_primary' => true, 'sort_order' => 0]);

        $this->get(route('archive.date', [
            'year' => 2026,
            'month' => 9,
            'day' => 11,
            'category' => $category->getKey(),
            'author' => $author->getKey(),
        ]))
            ->assertOk()
            ->assertSee('ফিল্টার করা আর্কাইভ')
            ->assertDontSee('ফিল্টার ছাড়া আর্কাইভ');
    }

    public function test_invalid_archive_dates_return_not_found(): void
    {
        $this->get('/archive/2026/13')
            ->assertNotFound();

        $this->get('/archive/2026/02/30')
            ->assertNotFound();

        $this->get('/archive/1969')
            ->assertNotFound();
    }

    public function test_archive_results_are_paginated(): void
    {
        Article::factory()
            ->count(14)
            ->published()
            ->create([
                'headline_bn' => 'পেজিনেটেড আর্কাইভ সংবাদ',
                'published_at' => '2026-09-11 10:00:00',
            ]);

        $this->get(route('archive.date', ['year' => 2026, 'month' => 9, 'day' => 11]))
            ->assertOk()
            ->assertSee('Go to page 2');
    }
}
