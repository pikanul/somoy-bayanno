<?php

namespace App\Filament\Resources\EpaperResource\Pages;

use App\Filament\Resources\EpaperResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEpaper extends CreateRecord
{
    protected static string $resource = EpaperResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl();
    }
}
