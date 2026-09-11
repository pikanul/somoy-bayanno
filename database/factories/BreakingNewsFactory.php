<?php

namespace Database\Factories;

use App\Enums\BreakingNewsStatus;
use App\Enums\BreakingNewsTargetType;
use App\Models\BreakingNews;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BreakingNews>
 */
class BreakingNewsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'headline_bn' => fake()->sentence(6),
            'target_type' => BreakingNewsTargetType::None,
            'article_id' => null,
            'external_url' => null,
            'priority' => fake()->numberBetween(0, 100),
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'status' => BreakingNewsStatus::Active,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
