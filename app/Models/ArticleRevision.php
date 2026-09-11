<?php

namespace App\Models;

use Database\Factories\ArticleRevisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'article_id',
    'revision_number',
    'version',
    'snapshot',
    'change_summary',
    'changed_by',
    'created_by',
])]
class ArticleRevision extends Model
{
    /** @use HasFactory<ArticleRevisionFactory> */
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'revision_number' => 'integer',
            'version' => 'integer',
            'snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /** @return array<string, array{from: mixed, to: mixed}> */
    public function compareWith(?ArticleRevision $previousRevision): array
    {
        if ($previousRevision === null) {
            return [];
        }

        return collect($this->snapshot)
            ->mapWithKeys(function (mixed $value, string $key) use ($previousRevision): array {
                $previous = $previousRevision->snapshot[$key] ?? null;

                if ($previous === $value) {
                    return [];
                }

                return [$key => ['from' => $previous, 'to' => $value]];
            })
            ->all();
    }
}
