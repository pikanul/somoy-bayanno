<?php

namespace App\Filament\Resources\GalleryResource\Pages;

use App\Filament\Resources\GalleryResource;
use App\Services\GalleryAdministrationService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditGallery extends EditRecord
{
    protected static string $resource = GalleryResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(GalleryAdministrationService::class)->update(Auth::user(), $record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
