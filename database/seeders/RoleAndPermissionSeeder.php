<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = config('access.permissions', []);
        $roles = config('access.roles', []);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($rolePermissions === ['*'] ? $permissions : $rolePermissions);
        }

        User::query()
            ->whereNotNull('role')
            ->where('role', '!=', '')
            ->get()
            ->each(function (User $user) use ($roles): void {
                if (array_key_exists($user->role, $roles)) {
                    $user->syncRoles([$user->role]);
                }
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}