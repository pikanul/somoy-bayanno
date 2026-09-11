<?php

namespace App\Models;

use App\Enums\BreakingNewsStatus;
use App\Enums\BreakingNewsTargetType;
use Database\Factories\BreakingNewsFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'headline_bn',
    'target_type',
    'article_id',
    'external_url',
    'priority',
    'starts_at',
    'ends_at',
    'status',
    'created_by',
    'updated_by',
])]
class BreakingNews extends Model
{
    /** @use HasFactory<BreakingNewsFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'target_type' => BreakingNewsTargetType::class,
            'status' => BreakingNewsStatus::class,
            'priority' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @param Builder<BreakingNews> $query */
    public function scopeCurrentlyActive(Builder $query): void
    {
        $query
            ->where('status', BreakingNewsStatus::Active->value)
            ->where(function (Builder $query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->orderByDesc('priority')
            ->orderByDesc('starts_at')
            ->orderByDesc('created_at');
    }

    public function isCurrentlyActive(): bool
    {
        return $this->status === BreakingNewsStatus::Active
            && ($this->starts_at === null || $this->starts_at->lte(now()))
            && ($this->ends_at === null || $this->ends_at->gt(now()));
    }
}
