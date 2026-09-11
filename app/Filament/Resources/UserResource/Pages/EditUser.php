<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Services\UserAdministrationService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['role'] = $this->getRecord()->roles()->pluck('name')->first();
        unset($data['password']);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UserAdministrationService::class)->update(
            Auth::user(),
            $record,
            $data,
            array_key_exists('role', $this->data) ? $this->selectedRoles() : null,
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /** @return array<int, string> */
    private function selectedRoles(): array
    {
        $role = $this->data['role'] ?? null;

        return filled($role) ? [(string) $role] : [];
    }
}
