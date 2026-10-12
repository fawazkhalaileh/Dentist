<?php

namespace App\Support;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the roles and permissions defined in App\Enums\Role::permissions().
 * Idempotent and creates no users, so it is safe in every environment.
 * Runs automatically after every `php artisan migrate` (see AppServiceProvider).
 */
final class RolesAndPermissions
{
    public static function sync(): void
    {
        // Nothing to do until the spatie tables exist (e.g. a partial migrate --path run).
        if (! Schema::hasTable(config('permission.table_names.roles')) || ! Schema::hasTable(config('permission.table_names.permissions'))) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'web');
        }

        foreach (Role::cases() as $role) {
            RoleModel::findOrCreate($role->value, 'web')
                ->syncPermissions(array_map(fn (Permission $p) => $p->value, $role->permissions()));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
