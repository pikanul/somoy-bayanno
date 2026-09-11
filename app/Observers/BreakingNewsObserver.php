<?php

namespace App\Observers;

use App\Models\BreakingNews;
use App\Services\PublicContentCache;

class BreakingNewsObserver
{
    /**
     * Handle the BreakingNews "created" event.
     */
    public function created(BreakingNews $breakingNews): void
    {
        $this->invalidatePublicContent();
    }

    /**
     * Handle the BreakingNews "updated" event.
     */
    public function updated(BreakingNews $breakingNews): void
    {
        $this->invalidatePublicContent();
    }

    /**
     * Handle the BreakingNews "deleted" event.
     */
    public function deleted(BreakingNews $breakingNews): void
    {
        $this->invalidatePublicContent();
    }

    /**
     * Handle the BreakingNews "restored" event.
     */
    public function restored(BreakingNews $breakingNews): void
    {
        $this->invalidatePublicContent();
    }

    /**
     * Handle the BreakingNews "force deleted" event.
     */
    public function forceDeleted(BreakingNews $breakingNews): void
    {
        $this->invalidatePublicContent();
    }

    private function invalidatePublicContent(): void
    {
        app(PublicContentCache::class)->flushPublicContent();
    }
}
