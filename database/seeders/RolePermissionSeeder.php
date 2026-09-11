<?php

namespace Database\Seeders;

use App\Support\Security\Rbac;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Rbac::permissions() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (Rbac::roles() as $roleName) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions(Rbac::rolePermissions()[$roleName] ?? []);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
