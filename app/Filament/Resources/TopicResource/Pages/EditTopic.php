<?php

namespace App\Filament\Resources\TopicResource\Pages;

use App\Filament\Resources\TopicResource;
use App\Services\TopicAdministrationService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditTopic extends EditRecord
{
    protected static string $resource = TopicResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(TopicAdministrationService::class)->update(Auth::user(), $record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
