<?php

namespace App\Observers;

use App\Models\Article;
use App\Services\PublicContentCache;

class ArticleObserver
{
    /**
     * Handle the Article "created" event.
     */
    public function created(Article $article): void
    {
        $this->invalidate($article);
    }

    /**
     * Handle the Article "updated" event.
     */
    public function updated(Article $article): void
    {
        $this->invalidate($article);
    }

    /**
     * Handle the Article "deleted" event.
     */
    public function deleted(Article $article): void
    {
        $this->invalidate($article);
    }

    /**
     * Handle the Article "restored" event.
     */
    public function restored(Article $article): void
    {
        $this->invalidate($article);
    }

    /**
     * Handle the Article "force deleted" event.
     */
    public function forceDeleted(Article $article): void
    {
        $this->invalidate($article);
    }

    private function invalidate(Article $article): void
    {
        $cache = app(PublicContentCache::class);

        $cache->forgetArticle($article);
        $cache->flushPublicContent();
    }
}
