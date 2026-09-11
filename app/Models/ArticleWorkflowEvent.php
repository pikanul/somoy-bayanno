<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use App\Enums\ArticleWorkflowAction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'article_id',
    'action',
    'previous_status',
    'new_status',
    'comment',
    'actor_id',
    'acted_at',
])]
class ArticleWorkflowEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'action' => ArticleWorkflowAction::class,
            'previous_status' => ArticleStatus::class,
            'new_status' => ArticleStatus::class,
            'acted_at' => 'datetime',
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
        return $this->belongsTo(User::class, 'actor_id');
    }
}
