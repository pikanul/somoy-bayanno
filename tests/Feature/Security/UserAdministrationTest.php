<?php

namespace Tests\Feature\Security;

use App\Enums\UserStatus;
use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Services\UserAdministrationService;
use App\Support\Security\Rbac;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized_editor_cannot_access_user_administration(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $editor = User::factory()->create();
        $editor->assignRole(Rbac::NEWS_EDITOR);

        $this->actingAs($editor)
            ->get('/newsroom/users')
            ->assertForbidden();
    }

    public function test_authorized_admin_can_create_user_securely(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = $this->superAdmin();
        $user = app(UserAdministrationService::class)->create($admin, [
            'name' => 'Desk Reporter',
            'email' => 'desk.reporter@example.com',
            'password' => 'A secure password for tests 123!',
            'status' => UserStatus::Active->value,
        ], [Rbac::REPORTER]);

        $this->assertSame('Desk Reporter', $user->name);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertTrue(Hash::check('A secure password for tests 123!', $user->password));
        $this->assertNotSame('A secure password for tests 123!', $user->password);
        $this->assertTrue($user->hasRole(Rbac::REPORTER));
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_email_must_be_unique(): void
    {
        $this->seed(RolePermissionSeeder::class);

        User::factory()->create(['email' => 'duplicate@example.com']);

        $this->expectException(ValidationException::class);

        app(UserAdministrationService::class)->create($this->superAdmin(), [
            'name' => 'Duplicate User',
            'email' => 'duplicate@example.com',
            'password' => 'A secure password for tests 123!',
            'status' => UserStatus::Active->value,
        ]);
    }

    public function test_authorized_admin_can_assign_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = $this->superAdmin();
        $user = User::factory()->create();

        app(UserAdministrationService::class)->update($admin, $user, [
            'name' => $user->name,
            'email' => $user->email,
            'password' => null,
            'status' => UserStatus::Active->value,
        ], [Rbac::SECTION_EDITOR]);

        $this->assertTrue($user->refresh()->hasRole(Rbac::SECTION_EDITOR));
    }

    public function test_disabled_user_cannot_log_in_to_filament_panel(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->disabled()->create([
            'email' => 'disabled@example.com',
            'password' => 'A secure password for tests 123!',
        ]);
        $user->assignRole(Rbac::NEWS_EDITOR);

        $panel = Filament::getPanel('admin');
        $credentials = [
            'email' => 'disabled@example.com',
            'password' => 'A secure password for tests 123!',
        ];

        $this->assertFalse($user->canAccessPanel($panel));
        $this->assertFalse(auth()->attemptWhen($credentials, fn (User $user): bool => $user->canAccessPanel($panel)));
        $this->assertGuest();
    }

    public function test_reporter_cannot_elevate_their_own_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $reporter = User::factory()->create();
        $reporter->assignRole(Rbac::REPORTER);

        $this->expectException(AuthorizationException::class);

        app(UserAdministrationService::class)->update($reporter, $reporter, [
            'name' => $reporter->name,
            'email' => $reporter->email,
            'password' => null,
            'status' => UserStatus::Active->value,
        ], [Rbac::SUPER_ADMIN]);
    }

    public function test_final_active_super_admin_cannot_be_disabled_or_deleted(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = $this->superAdmin();

        $this->assertFalse($admin->can('delete', $admin));
        $this->assertFalse($admin->can('disable', $admin));
    }

    public function test_user_resource_uses_policy_authorization(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $editor = User::factory()->create();
        $editor->assignRole(Rbac::NEWS_EDITOR);

        $admin = $this->superAdmin();

        $this->actingAs($editor);
        $this->assertFalse(UserResource::canAccess());

        $this->actingAs($admin);
        $this->assertTrue(UserResource::canAccess());
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Rbac::SUPER_ADMIN);

        return $admin;
    }
}
