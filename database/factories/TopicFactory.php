<?php

namespace Database\Factories;

use App\Enums\TopicStatus;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Topic>
 */
class TopicFactory extends Factory
{
    protected $model = Topic::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name_bn' => $name,
            'name_en' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->optional()->sentence(),
            'featured' => false,
            'status' => TopicStatus::Active,
            'seo_title' => fake()->optional()->sentence(4),
            'seo_description' => fake()->optional()->sentence(10),
        ];
    }
}
