<?php

namespace App\Filament\Resources\LiveStreamResource\Pages;

use App\Filament\Resources\LiveStreamResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLiveStream extends CreateRecord
{
    protected static string $resource = LiveStreamResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl();
    }
}
