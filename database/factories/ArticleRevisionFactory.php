<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleRevision>
 */
class ArticleRevisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'revision_number' => 1,
            'version' => 1,
            'snapshot' => [
                'headline_bn' => 'পুরোনো শিরোনাম',
                'body_bn' => 'পুরোনো কনটেন্ট',
            ],
            'change_summary' => 'Initial editorial snapshot',
            'changed_by' => User::factory(),
            'created_by' => User::factory(),
            'created_at' => now(),
        ];
    }
}
