<?php

namespace Database\Factories;

use App\Enums\CategoryStatus;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name_bn' => $name,
            'name_en' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->optional()->sentence(),
            'status' => CategoryStatus::Active,
            'sort_order' => fake()->numberBetween(0, 100),
            'show_in_menu' => true,
            'seo_title' => fake()->optional()->sentence(4),
            'seo_description' => fake()->optional()->sentence(10),
        ];
    }
}
