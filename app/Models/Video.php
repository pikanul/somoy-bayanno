<?php

namespace App\Models;

use App\Enums\VideoProvider;
use App\Enums\VideoStatus;
use App\Services\VideoProviderService;
use Database\Factories\VideoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'title_bn',
    'description_bn',
    'provider',
    'video_url',
    'provider_video_id',
    'thumbnail',
    'duration',
    'author_id',
    'category_id',
    'status',
    'published_at',
    'seo_title',
    'seo_description',
    'created_by',
    'updated_by',
])]
class Video extends Model
{
    /** @use HasFactory<VideoFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'provider' => VideoProvider::class,
            'status' => VideoStatus::class,
            'duration' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Author, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function embedUrl(): string
    {
        return app(VideoProviderService::class)->embedUrl($this);
    }
}
