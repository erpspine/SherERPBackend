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
        $normalizedRoles = collect(array_keys($roles))
            ->mapWithKeys(fn(string $roleName): array => [mb_strtolower($roleName) => $roleName])
            ->all();

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
            ->each(function (User $user) use ($normalizedRoles): void {
                $resolvedRole = $normalizedRoles[mb_strtolower((string) $user->role)] ?? null;

                if ($resolvedRole === null) {
                    return;
                }

                if ($user->role !== $resolvedRole) {
                    $user->role = $resolvedRole;
                    $user->save();
                }

                $user->syncRoles([$resolvedRole]);
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
