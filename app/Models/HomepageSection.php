<?php

namespace App\Models;

use App\Enums\HomepageSectionKey;
use App\Enums\HomepageSectionStatus;
use Database\Factories\HomepageSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'key',
    'title',
    'status',
    'sort_order',
    'published_at',
    'created_by',
    'updated_by',
    'published_by',
])]
class HomepageSection extends Model
{
    /** @use HasFactory<HomepageSectionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'key' => HomepageSectionKey::class,
            'status' => HomepageSectionStatus::class,
            'sort_order' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /** @return HasMany<HomepageItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(HomepageItem::class)->orderBy('sort_order');
    }

    /** @return BelongsTo<User, $this> */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
