<?php

namespace Database\Factories;

use App\Enums\AuthorStatus;
use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Author>
 */
class AuthorFactory extends Factory
{
    protected $model = Author::class;

    public function definition(): array
    {
        $name = fake()->unique()->name();

        return [
            'name_bn' => $name,
            'name_en' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'designation' => fake()->optional()->jobTitle(),
            'bio_bn' => fake()->optional()->paragraph(),
            'bio_en' => fake()->optional()->paragraph(),
            'photo' => null,
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'facebook_url' => null,
            'x_url' => null,
            'linkedin_url' => null,
            'website_url' => null,
            'status' => AuthorStatus::Active,
            'featured' => false,
            'seo_title' => fake()->optional()->sentence(4),
            'seo_description' => fake()->optional()->sentence(10),
        ];
    }
}
