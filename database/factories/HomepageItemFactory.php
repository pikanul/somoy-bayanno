<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\HomepageItem;
use App\Models\HomepageSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomepageItem>
 */
class HomepageItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'homepage_section_id' => HomepageSection::factory(),
            'article_id' => Article::factory()->published(),
            'label' => fake()->optional()->words(2, true),
            'sort_order' => fake()->numberBetween(0, 10),
            'starts_at' => null,
            'ends_at' => null,
        ];
    }
}
