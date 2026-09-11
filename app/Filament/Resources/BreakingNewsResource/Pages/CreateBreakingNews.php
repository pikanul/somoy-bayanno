<?php

namespace App\Filament\Resources\BreakingNewsResource\Pages;

use App\Filament\Resources\BreakingNewsResource;
use App\Services\BreakingNewsAdministrationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateBreakingNews extends CreateRecord
{
    protected static string $resource = BreakingNewsResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        return app(BreakingNewsAdministrationService::class)->create(Auth::user(), $data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl();
    }
}
