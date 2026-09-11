<?php

namespace Database\Factories;

use App\Enums\HomepageSectionKey;
use App\Enums\HomepageSectionStatus;
use App\Models\HomepageSection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomepageSection>
 */
class HomepageSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->randomElement(HomepageSectionKey::cases()),
            'title' => fake()->words(2, true),
            'status' => HomepageSectionStatus::Draft,
            'sort_order' => fake()->numberBetween(0, 20),
            'published_at' => null,
            'created_by' => User::factory(),
            'updated_by' => null,
            'published_by' => null,
        ];
    }
}
