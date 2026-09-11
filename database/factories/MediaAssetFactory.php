<?php

namespace Database\Factories;

use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MediaAsset>
 */
class MediaAssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::random(40).'.jpg';

        return [
            'type' => 'image',
            'original_name' => fake()->word().'.jpg',
            'storage_disk' => 'public',
            'path' => 'media/originals/'.now()->format('Y/m').'/'.$name,
            'external_url' => null,
            'mime_type' => 'image/jpeg',
            'file_size' => fake()->numberBetween(1000, 250000),
            'width' => 1200,
            'height' => 800,
            'alt_text' => fake()->sentence(4),
            'caption' => fake()->sentence(),
            'credit' => fake()->company(),
            'photographer' => fake()->name(),
            'source_name' => fake()->company(),
            'source_url' => fake()->url(),
            'copyright' => fake()->sentence(3),
            'uploaded_by' => User::factory(),
        ];
    }
}
