<?php

namespace App\Models;

use App\Enums\TopicStatus;
use Database\Factories\TopicFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name_bn',
    'name_en',
    'slug',
    'description',
    'featured',
    'status',
    'seo_title',
    'seo_description',
])]
class Topic extends Model
{
    /** @use HasFactory<TopicFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'status' => TopicStatus::class,
        ];
    }

    /** @return BelongsToMany<Article, $this> */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class)->withTimestamps();
    }
}
