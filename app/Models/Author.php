<?php

namespace App\Models;

use App\Enums\AuthorStatus;
use Database\Factories\AuthorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id',
    'name_bn',
    'name_en',
    'slug',
    'designation',
    'bio_bn',
    'bio_en',
    'photo',
    'email',
    'phone',
    'address',
    'facebook_url',
    'x_url',
    'linkedin_url',
    'website_url',
    'status',
    'featured',
    'organization_level',
    'sort_order',
    'seo_title',
    'seo_description',
])]
#[Hidden(['phone'])]
class Author extends Model
{
    /** @use HasFactory<AuthorFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'organization_level' => 'integer',
            'sort_order' => 'integer',
            'status' => AuthorStatus::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<Article, $this> */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class)
            ->withPivot(['credit', 'sort_order'])
            ->withTimestamps();
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === AuthorStatus::Active && ! $this->trashed();
    }
}
