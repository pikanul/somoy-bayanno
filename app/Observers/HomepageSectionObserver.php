<?php

namespace App\Observers;

use App\Models\HomepageSection;
use App\Services\PublicContentCache;

class HomepageSectionObserver
{
    /**
     * Handle the HomepageSection "created" event.
     */
    public function created(HomepageSection $homepageSection): void
    {
        $this->invalidatePublicContent();
    }

    /**
     * Handle the HomepageSection "updated" event.
     */
    public function updated(HomepageSection $homepageSection): void
    {
        $this->invalidatePublicContent();
    }

    /**
     * Handle the HomepageSection "deleted" event.
     */
    public function deleted(HomepageSection $homepageSection): void
    {
        $this->invalidatePublicContent();
    }

    /**
     * Handle the HomepageSection "restored" event.
     */
    public function restored(HomepageSection $homepageSection): void
    {
        $this->invalidatePublicContent();
    }

    /**
     * Handle the HomepageSection "force deleted" event.
     */
    public function forceDeleted(HomepageSection $homepageSection): void
    {
        $this->invalidatePublicContent();
    }

    private function invalidatePublicContent(): void
    {
        app(PublicContentCache::class)->flushPublicContent();
    }
}
