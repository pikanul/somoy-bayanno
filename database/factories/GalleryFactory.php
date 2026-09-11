<?php

namespace Database\Factories;

use App\Enums\GalleryStatus;
use App\Models\Author;
use App\Models\Category;
use App\Models\Gallery;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Gallery>
 */
class GalleryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->paragraph(),
            'cover_image_id' => MediaAsset::factory(),
            'author_id' => Author::factory(),
            'photographer' => fake()->name(),
            'category_id' => Category::factory(),
            'status' => GalleryStatus::Draft,
            'published_at' => null,
            'seo_title' => fake()->sentence(5),
            'seo_description' => fake()->sentence(12),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
