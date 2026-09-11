<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Enums\ArticleVisibility;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $headline = 'বাংলা সংবাদ '.fake()->unique()->numberBetween(1000, 999999);

        return [
            'type' => ArticleType::Standard,
            'headline_bn' => $headline,
            'headline_en' => fake()->sentence(6),
            'slug' => Str::slug(fake()->unique()->sentence(4)),
            'summary_bn' => 'সংক্ষিপ্ত সংবাদ সারাংশ',
            'body_bn' => 'এটি পরীক্ষার জন্য তৈরি বাংলা সংবাদ প্রতিবেদন।',
            'primary_category_id' => Category::factory(),
            'status' => ArticleStatus::Draft,
            'visibility' => ArticleVisibility::Public,
            'comments_enabled' => true,
            'is_breaking' => false,
            'is_featured' => false,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => ArticleStatus::Published,
            'visibility' => ArticleVisibility::Public,
            'published_at' => now()->subMinute(),
            'published_by' => User::factory(),
        ]);
    }
}
