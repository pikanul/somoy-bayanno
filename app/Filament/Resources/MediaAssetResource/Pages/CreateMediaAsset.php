<?php

namespace App\Filament\Resources\MediaAssetResource\Pages;

use App\Filament\Resources\MediaAssetResource;
use App\Services\MediaLibraryService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateMediaAsset extends CreateRecord
{
    protected static string $resource = MediaAssetResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        $metadata = collect($data)->only([
            'alt_text',
            'caption',
            'credit',
            'photographer',
            'source_name',
            'source_url',
            'copyright',
        ])->all();

        if (($data['source_mode'] ?? 'upload') === 'external') {
            return app(MediaLibraryService::class)->importExternalImage(Auth::user(), (string) $data['external_url_input'], $metadata);
        }

        $file = $data['local_upload'] ?? null;

        if (! $file) {
            throw ValidationException::withMessages(['local_upload' => 'Choose an image to upload.']);
        }

        if (is_array($file)) {
            $file = reset($file);
        }

        return app(MediaLibraryService::class)->storeUploadedImage(Auth::user(), $file, $metadata);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl();
    }
}
