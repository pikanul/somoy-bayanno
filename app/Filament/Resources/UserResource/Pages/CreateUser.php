<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Services\UserAdministrationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        return app(UserAdministrationService::class)->create(
            Auth::user(),
            $data,
            $this->selectedRoles(),
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl();
    }

    /** @return array<int, string> */
    private function selectedRoles(): array
    {
        $role = $this->data['role'] ?? null;

        return filled($role) ? [(string) $role] : [];
    }
}
