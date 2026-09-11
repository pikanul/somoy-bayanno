<?php

namespace Tests\Feature\Security;

use App\Enums\ArticleStatus;
use App\Enums\ArticleVisibility;
use App\Enums\BreakingNewsStatus;
use App\Enums\BreakingNewsTargetType;
use App\Models\Article;
use App\Models\BreakingNews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicFrontendFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_homepage_renders_responsive_blade_shell(): void
    {
        Cache::forget('public-homepage-v1');

        Article::factory()->create([
            'headline_bn' => 'প্রধান সংবাদ',
            'summary_bn' => 'সংক্ষিপ্ত সারাংশ',
            'status' => ArticleStatus::Published,
            'visibility' => ArticleVisibility::Public,
            'published_at' => now()->subMinute(),
        ]);

        BreakingNews::factory()->create([
            'headline_bn' => 'তাৎক্ষণিক খবর',
            'target_type' => BreakingNewsTargetType::None,
            'status' => BreakingNewsStatus::Active,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
        ]);

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('দৈনিক সময় বায়ান্ন')
            ->assertSee('The Daily Somoy Bayanno')
            ->assertSee('প্রধান নেভিগেশন')
            ->assertSee('ব্রেকিং')
            ->assertSee('তাৎক্ষণিক খবর')
            ->assertSee('প্রধান সংবাদ')
            ->assertSee('Advertisement');
    }

    public function test_public_homepage_v1_renders_required_sections_without_unpublished_content(): void
    {
        Cache::forget('public-homepage-v1');

        Article::factory()->published()->create([
            'headline_bn' => 'প্রকাশিত হিরো সংবাদ',
            'slug' => 'published-hero-news',
            'is_featured' => true,
        ]);

        Article::factory()->create([
            'headline_bn' => 'অপ্রকাশিত সংবাদ',
            'slug' => 'draft-homepage-news',
            'status' => ArticleStatus::Draft,
            'published_at' => null,
        ]);

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('প্রকাশিত হিরো সংবাদ')
            ->assertSee('সর্বাধিক পঠিত')
            ->assertSee('বিভাগীয় হাইলাইটস')
            ->assertSee('সর্বশেষ সংবাদ')
            ->assertSee('জাতীয়')
            ->assertSee('রাজনীতি')
            ->assertSee('আন্তর্জাতিক')
            ->assertSee('অর্থনীতি')
            ->assertSee('খেলা')
            ->assertSee('বিনোদন')
            ->assertSee('প্রযুক্তি')
            ->assertSee('লাইফস্টাইল')
            ->assertSee('ভিডিও')
            ->assertSee('ছবিঘর')
            ->assertSee('মতামত')
            ->assertDontSee('অপ্রকাশিত সংবাদ');
    }
}
