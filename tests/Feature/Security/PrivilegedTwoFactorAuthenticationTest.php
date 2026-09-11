<?php

namespace Tests\Feature\Security;

use App\Enums\UserStatus;
use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Services\UserAdministrationService;
use App\Support\Security\Rbac;
use Database\Seeders\RolePermissionSeeder;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PrivilegedTwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_factor_enabled_login_requires_valid_app_code(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = $this->privilegedUserWithTwoFactor();
        $code = AppAuthentication::make()->getCurrentCode($user);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertSet('userUndertakingMultiFactorAuthentication', fn (?string $value): bool => filled($value))
            ->set('data.multiFactor.app.code', $code)
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_two_factor_code_is_rejected(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = $this->privilegedUserWithTwoFactor();

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->set('data.multiFactor.app.code', '000000')
            ->call('authenticate')
            ->assertHasErrors(['data.multiFactor.app.code']);

        $this->assertGuest();
    }

    public function test_recovery_code_can_be_used_once_for_two_factor_login(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $recoveryCode = 'recovery-code-for-tests';
        $user = $this->privilegedUserWithTwoFactor([
            Hash::make($recoveryCode),
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->set('data.multiFactor.app.useRecoveryCode', true)
            ->set('data.multiFactor.app.recoveryCode', $recoveryCode)
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
        $this->assertSame([], $user->refresh()->getAppAuthenticationRecoveryCodes());
    }

    public function test_disabled_privileged_account_cannot_start_two_factor_login(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = $this->privilegedUserWithTwoFactor();
        $user->update(['status' => UserStatus::Disabled]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email'])
            ->assertSet('userUndertakingMultiFactorAuthentication', null);

        $this->assertGuest();
    }

    public function test_privileged_users_must_enroll_two_factor_before_panel_access(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(Rbac::EDITOR_IN_CHIEF);

        $this->assertTrue($user->requiresTwoFactorAuthentication());
        $this->assertFalse($user->hasTwoFactorAuthenticationEnabled());

        $this->actingAs($user)
            ->get('/newsroom')
            ->assertRedirect(Filament::getPanel('admin')->getSetUpRequiredMultiFactorAuthenticationUrl());
    }

    public function test_authorized_admin_can_reset_two_factor_without_exposing_recovery_codes(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(Rbac::SUPER_ADMIN);

        $target = $this->privilegedUserWithTwoFactor([
            Hash::make('secret-recovery-code'),
        ]);

        app(UserAdministrationService::class)->resetTwoFactorAuthentication($admin, $target);

        $target->refresh();

        $this->assertFalse($target->hasTwoFactorAuthenticationEnabled());
        $this->assertNull($target->getAppAuthenticationRecoveryCodes());
        $this->actingAs($admin);
        $this->assertTrue(UserResource::canAccess());
    }

    /** @param  array<int, string>|null  $recoveryCodes */
    private function privilegedUserWithTwoFactor(?array $recoveryCodes = null): User
    {
        $user = User::factory()->create();
        $user->assignRole(Rbac::SUPER_ADMIN);
        $user->forceFill([
            'app_authentication_secret' => AppAuthentication::make()->generateSecret(),
            'app_authentication_recovery_codes' => $recoveryCodes ?? [
                Hash::make('first-recovery-code'),
            ],
        ])->save();

        return $user->refresh();
    }
}
