<?php

namespace App\Services;

use App\Models\User;
use App\Support\Security\Rbac;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserAdministrationService
{
    public function __construct(private ActivityLogger $activityLogger) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string>  $roles
     */
    public function create(User $actor, array $attributes, array $roles = []): User
    {
        Gate::forUser($actor)->authorize('create', User::class);

        $attributes = $this->validatedAttributes($attributes);

        $this->authorizeRoleAssignment($actor, null, $roles);

        $user = User::query()->create(Arr::only($attributes, [
            'name',
            'email',
            'password',
            'status',
        ]));

        if ($roles !== []) {
            $user->syncRoles($roles);
        }

        $this->activityLogger->record(
            'user.create',
            $actor,
            $user,
            newValues: [
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'roles' => $roles,
            ],
        );

        if ($roles !== []) {
            $this->activityLogger->record(
                'role.change',
                $actor,
                $user,
                newValues: ['roles' => $roles],
            );
        }

        Log::info('User administration: user created', [
            'actor_id' => $actor->getKey(),
            'target_user_id' => $user->getKey(),
            'roles' => $roles,
        ]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string>|null  $roles
     */
    public function update(User $actor, User $user, array $attributes, ?array $roles = null): User
    {
        Gate::forUser($actor)->authorize('update', $user);

        $attributes = $this->validatedAttributes($attributes, $user);

        if (($attributes['status'] ?? null) === 'disabled') {
            Gate::forUser($actor)->authorize('disable', $user);
        }

        if ($roles !== null) {
            $this->authorizeRoleAssignment($actor, $user, $roles);
        }

        $data = Arr::only($attributes, [
            'name',
            'email',
            'password',
            'status',
        ]);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $oldValues = [
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'roles' => $user->roles()->pluck('name')->all(),
        ];

        $user->update($data);

        if ($roles !== null) {
            $user->syncRoles($roles);
        }

        $user = $user->refresh();
        $newValues = [
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'roles' => $user->roles()->pluck('name')->all(),
        ];
        $diff = $this->activityLogger->safeDiff($oldValues, $newValues);
        $oldStatus = $oldValues['status'] instanceof \BackedEnum ? $oldValues['status']->value : $oldValues['status'];
        $newStatus = $newValues['status'] instanceof \BackedEnum ? $newValues['status']->value : $newValues['status'];

        $this->activityLogger->record(
            $oldStatus !== 'disabled' && $newStatus === 'disabled' ? 'user.disable' : 'user.update',
            $actor,
            $user,
            oldValues: $diff['old'],
            newValues: $diff['new'],
        );

        if ($roles !== null && ($oldValues['roles'] !== $newValues['roles'])) {
            $this->activityLogger->record(
                'role.change',
                $actor,
                $user,
                oldValues: ['roles' => $oldValues['roles']],
                newValues: ['roles' => $newValues['roles']],
            );
        }

        Log::info('User administration: user updated', [
            'actor_id' => $actor->getKey(),
            'target_user_id' => $user->getKey(),
            'changed' => array_values(array_diff(array_keys($data), ['password'])),
            'roles_changed' => $roles !== null,
        ]);

        return $user;
    }

    /** @param  array<string, mixed>  $attributes */
    private function validatedAttributes(array $attributes, ?User $user = null): array
    {
        return Validator::make($attributes, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', Password::default()],
            'status' => ['required', Rule::in(['active', 'disabled'])],
        ])->validate();
    }

    public function disable(User $actor, User $user): User
    {
        Gate::forUser($actor)->authorize('disable', $user);

        $user->update(['status' => 'disabled']);

        $this->activityLogger->record(
            'user.disable',
            $actor,
            $user,
            oldValues: ['status' => 'active'],
            newValues: ['status' => 'disabled'],
        );

        Log::info('User administration: user disabled', [
            'actor_id' => $actor->getKey(),
            'target_user_id' => $user->getKey(),
        ]);

        return $user->refresh();
    }

    public function resetTwoFactorAuthentication(User $actor, User $user): User
    {
        Gate::forUser($actor)->authorize('update', $user);

        $wasEnabled = $user->hasTwoFactorAuthenticationEnabled();

        $user->forceFill([
            'app_authentication_secret' => null,
            'app_authentication_recovery_codes' => null,
        ])->save();

        $this->activityLogger->record(
            'user.two-factor-reset',
            $actor,
            $user,
            oldValues: ['two_factor_enabled' => $wasEnabled],
            newValues: ['two_factor_enabled' => false],
        );

        Log::info('User administration: two-factor authentication reset', [
            'actor_id' => $actor->getKey(),
            'target_user_id' => $user->getKey(),
        ]);

        return $user->refresh();
    }

    public function delete(User $actor, User $user): void
    {
        Gate::forUser($actor)->authorize('delete', $user);

        $user->delete();

        Log::info('User administration: user soft deleted', [
            'actor_id' => $actor->getKey(),
            'target_user_id' => $user->getKey(),
        ]);
    }

    /** @param  array<int, string>  $roles */
    private function authorizeRoleAssignment(User $actor, ?User $target, array $roles): void
    {
        if ($roles === []) {
            return;
        }

        Gate::forUser($actor)->authorize('assignRole', [User::class, $target, $roles]);

        $unknownRoles = array_diff($roles, Rbac::roles());

        if ($unknownRoles !== []) {
            throw new AuthorizationException('Unknown role assignment denied.');
        }
    }
}
