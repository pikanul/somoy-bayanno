<?php

namespace App\Models;

use App\Enums\CategoryStatus;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name_bn',
    'name_en',
    'slug',
    'description',
    'parent_id',
    'status',
    'sort_order',
    'show_in_menu',
    'seo_title',
    'seo_description',
])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'show_in_menu' => 'boolean',
            'sort_order' => 'integer',
            'status' => CategoryStatus::class,
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Category, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name_bn');
    }

    /** @return HasMany<Article, $this> */
    public function primaryArticles(): HasMany
    {
        return $this->hasMany(Article::class, 'primary_category_id');
    }

    /** @return BelongsToMany<Article, $this> */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_category')
            ->withPivot(['is_primary', 'sort_order'])
            ->withTimestamps();
    }

    public function hasChild(Category $category): bool
    {
        return $this->children()
            ->whereKey($category->getKey())
            ->exists();
    }

    public function hasDescendant(Category $category): bool
    {
        $children = $this->children()->get(['id', 'parent_id']);

        foreach ($children as $child) {
            if ($child->is($category) || $child->hasDescendant($category)) {
                return true;
            }
        }

        return false;
    }
}
