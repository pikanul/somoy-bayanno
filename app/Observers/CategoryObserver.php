<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\PublicContentCache;

class CategoryObserver
{
    /**
     * Handle the Category "created" event.
     */
    public function created(Category $category): void
    {
        $this->invalidatePublicContent();
    }

    /**
     * Handle the Category "updated" event.
     */
    public function updated(Category $category): void
    {
        $this->invalidatePublicContent();
    }

    /**
     * Handle the Category "deleted" event.
     */
    public function deleted(Category $category): void
    {
        $this->invalidatePublicContent();
    }

    /**
     * Handle the Category "restored" event.
     */
    public function restored(Category $category): void
    {
        $this->invalidatePublicContent();
    }

    /**
     * Handle the Category "force deleted" event.
     */
    public function forceDeleted(Category $category): void
    {
        $this->invalidatePublicContent();
    }

    private function invalidatePublicContent(): void
    {
        app(PublicContentCache::class)->flushPublicContent();
    }
}
