<?php

namespace App\Observers;

use App\Models\HomepageItem;
use App\Services\PublicContentCache;

class HomepageItemObserver
{
    /**
     * Handle the HomepageItem "created" event.
     */
    public function created(HomepageItem $homepageItem): void
    {
        $this->invalidatePublicContent();
    }

    /**
     * Handle the HomepageItem "updated" event.
     */
    public function updated(HomepageItem $homepageItem): void
    {
        $this->invalidatePublicContent();
    }

    /**
     * Handle the HomepageItem "deleted" event.
     */
    public function deleted(HomepageItem $homepageItem): void
    {
        $this->invalidatePublicContent();
    }

    /**
     * Handle the HomepageItem "restored" event.
     */
    public function restored(HomepageItem $homepageItem): void
    {
        $this->invalidatePublicContent();
    }

    /**
     * Handle the HomepageItem "force deleted" event.
     */
    public function forceDeleted(HomepageItem $homepageItem): void
    {
        $this->invalidatePublicContent();
    }

    private function invalidatePublicContent(): void
    {
        app(PublicContentCache::class)->flushPublicContent();
    }
}
