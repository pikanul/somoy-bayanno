<?php

namespace Database\Factories;

use App\Enums\VideoProvider;
use App\Enums\VideoStatus;
use App\Models\Author;
use App\Models\Category;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Video>
 */
class VideoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title_bn' => fake()->sentence(4),
            'description_bn' => fake()->paragraph(),
            'provider' => VideoProvider::YouTube,
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'provider_video_id' => 'dQw4w9WgXcQ',
            'thumbnail' => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
            'duration' => fake()->numberBetween(30, 3600),
            'author_id' => Author::factory(),
            'category_id' => Category::factory(),
            'status' => VideoStatus::Draft,
            'published_at' => null,
            'seo_title' => fake()->sentence(5),
            'seo_description' => fake()->sentence(12),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
