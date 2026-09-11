<?php

namespace Database\Factories;

use App\Models\Gallery;
use App\Models\GalleryItem;
use App\Models\MediaAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryItem>
 */
class GalleryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gallery_id' => Gallery::factory(),
            'media_id' => MediaAsset::factory(),
            'caption' => fake()->sentence(),
            'credit' => fake()->company(),
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}
