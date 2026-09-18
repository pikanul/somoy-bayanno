<?php

namespace App\Filament\Resources\CareerVacancyResource\Pages;

use App\Filament\Resources\CareerVacancyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCareerVacancies extends ListRecords
{
    protected static string $resource = CareerVacancyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
