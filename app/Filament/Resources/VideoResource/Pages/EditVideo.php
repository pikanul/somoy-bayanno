<?php

namespace App\Filament\Resources\VideoResource\Pages;

use App\Filament\Resources\VideoResource;
use App\Services\VideoAdministrationService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditVideo extends EditRecord
{
    protected static string $resource = VideoResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(VideoAdministrationService::class)->update(Auth::user(), $record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
