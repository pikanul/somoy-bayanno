<?php

namespace App\Filament\Resources\LiveStreamResource\Pages;

use App\Filament\Resources\LiveStreamResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLiveStreams extends ListRecords
{
    protected static string $resource = LiveStreamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
