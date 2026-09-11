<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Security\Rbac;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('user.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('user.view');
    }

    public function create(User $user): bool
    {
        return $user->can('user.create');
    }

    public function update(User $user, User $model): bool
    {
        if (! $user->can('user.update')) {
            return false;
        }

        if ($model->hasRole(Rbac::SUPER_ADMIN) && ! $user->hasRole(Rbac::SUPER_ADMIN)) {
            return false;
        }

        return true;
    }

    public function disable(User $user, User $model): Response
    {
        if (! $user->can('user.disable')) {
            return Response::deny();
        }

        if ($this->wouldRemoveFinalActiveSuperAdmin($model)) {
            return Response::deny('The final active Super Admin account cannot be disabled.');
        }

        return Response::allow();
    }

    /** @param  array<int, string>  $roles */
    public function assignRole(User $user, ?User $model, array $roles): Response
    {
        if (! $user->can('role.manage')) {
            return Response::deny();
        }

        if (in_array(Rbac::SUPER_ADMIN, $roles, true) && ! $user->hasRole(Rbac::SUPER_ADMIN)) {
            return Response::deny('Only Super Admins may assign the Super Admin role.');
        }

        if (
            $model?->hasRole(Rbac::SUPER_ADMIN)
            && ! in_array(Rbac::SUPER_ADMIN, $roles, true)
            && $this->wouldRemoveFinalActiveSuperAdmin($model)
        ) {
            return Response::deny('The final active Super Admin account cannot lose that role.');
        }

        return Response::allow();
    }

    public function delete(User $user, User $model): Response
    {
        if (! $user->can('user.delete')) {
            return Response::deny();
        }

        if ($this->wouldRemoveFinalActiveSuperAdmin($model)) {
            return Response::deny('The final active Super Admin account cannot be deleted.');
        }

        return Response::allow();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, User $model): bool
    {
        return $user->can('user.update');
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    private function wouldRemoveFinalActiveSuperAdmin(User $user): bool
    {
        if (! $user->hasRole(Rbac::SUPER_ADMIN)) {
            return false;
        }

        return User::role(Rbac::SUPER_ADMIN)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->count() <= 1;
    }
}
