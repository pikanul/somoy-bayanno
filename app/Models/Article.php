<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Enums\ArticleVisibility;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'type',
    'headline_bn',
    'headline_en',
    'short_headline_bn',
    'slug',
    'subheadline_bn',
    'summary_bn',
    'body_bn',
    'primary_category_id',
    'reporter_name',
    'location',
    'source_name',
    'source_url',
    'featured_media_id',
    'featured_image_url',
    'image_caption',
    'image_credit',
    'video_url',
    'status',
    'visibility',
    'comments_enabled',
    'is_breaking',
    'is_featured',
    'published_at',
    'scheduled_at',
    'updated_content_at',
    'seo_title',
    'seo_description',
    'canonical_url',
    'social_title',
    'social_description',
    'social_image',
    'social_media_id',
    'correction_note',
    'internal_editor_note',
    'created_by',
    'updated_by',
    'published_by',
])]
#[Hidden(['internal_editor_note'])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => ArticleType::class,
            'status' => ArticleStatus::class,
            'visibility' => ArticleVisibility::class,
            'comments_enabled' => 'boolean',
            'is_breaking' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'updated_content_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'primary_category_id');
    }

    /** @return BelongsToMany<Category, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'article_category')
            ->withPivot(['is_primary', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /** @return BelongsToMany<Topic, $this> */
    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(Topic::class)->withTimestamps();
    }

    /** @return BelongsToMany<Author, $this> */
    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class)
            ->withPivot(['credit', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /** @return BelongsTo<MediaAsset, $this> */
    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'featured_media_id');
    }

    /** @return BelongsTo<MediaAsset, $this> */
    public function socialMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'social_media_id');
    }

    /** @return HasMany<ArticleRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(ArticleRevision::class)->orderByDesc('revision_number');
    }

    /** @return HasMany<ArticleWorkflowEvent, $this> */
    public function workflowEvents(): HasMany
    {
        return $this->hasMany(ArticleWorkflowEvent::class)->orderByDesc('acted_at');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return BelongsTo<User, $this> */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /** @param Builder<Article> $query */
    public function scopePubliclyVisible(Builder $query): void
    {
        $query
            ->whereIn('status', array_map(
                fn (ArticleStatus $status): string => $status->value,
                ArticleStatus::publiclyReadable(),
            ))
            ->where('visibility', ArticleVisibility::Public->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPubliclyVisible(): bool
    {
        return in_array($this->status, ArticleStatus::publiclyReadable(), true)
            && $this->visibility === ArticleVisibility::Public
            && $this->published_at !== null
            && $this->published_at->lte(now())
            && ! $this->trashed();
    }
}
