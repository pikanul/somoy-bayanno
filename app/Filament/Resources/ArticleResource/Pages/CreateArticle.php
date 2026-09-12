<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use App\Services\ArticleAdministrationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        $data = ArticleResource::attachUploadedFeaturedPhoto($data);

        return app(ArticleAdministrationService::class)->create(Auth::user(), $data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl();
    }
}
