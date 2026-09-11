<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Support\Security\Rbac;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_permission_is_denied_by_laravel_gate(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();

        $this->actingAs($user);

        $this->expectException(AuthorizationException::class);

        Gate::authorize('article.publish');
    }

    public function test_reporter_cannot_publish_articles(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(Rbac::REPORTER);

        $this->assertTrue($user->can('article.create'));
        $this->assertTrue($user->can('article.edit-own'));
        $this->assertFalse($user->can('article.publish'));
        $this->assertFalse($user->can('role.manage'));
    }

    public function test_auditor_is_read_only(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(Rbac::AUDITOR);

        $this->assertTrue($user->can('article.view'));
        $this->assertTrue($user->can('activity-log.view'));
        $this->assertFalse($user->can('article.create'));
        $this->assertFalse($user->can('settings.manage'));
        $this->assertFalse($user->can('user.update'));
    }

    public function test_super_admin_has_full_gate_access(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(Rbac::SUPER_ADMIN);

        $this->assertTrue($user->can('article.publish'));
        $this->assertTrue($user->can('permission.manage'));
        $this->assertTrue($user->can('backup.manage'));
        $this->assertTrue($user->can('settings.manage'));
    }

    public function test_only_users_with_newsroom_roles_can_access_filament_panel(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $panel = Filament::getPanel('admin');
        $unassignedUser = User::factory()->create();
        $editor = User::factory()->create();
        $editor->assignRole(Rbac::NEWS_EDITOR);

        $this->assertInstanceOf(Panel::class, $panel);
        $this->assertFalse($unassignedUser->canAccessPanel($panel));
        $this->assertTrue($editor->canAccessPanel($panel));
    }

    public function test_role_permission_seeder_is_idempotent(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->assertSame(count(Rbac::roles()), Role::query()->count());
        $this->assertDatabaseHas('roles', ['name' => Rbac::SUPER_ADMIN, 'guard_name' => 'web']);
        $this->assertDatabaseHas('permissions', ['name' => 'article.publish', 'guard_name' => 'web']);
    }
}
