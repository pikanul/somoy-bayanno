<?php

namespace App\Filament\Resources\EpaperResource\Pages;

use App\Filament\Resources\EpaperResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEpaper extends EditRecord
{
    protected static string $resource = EpaperResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
