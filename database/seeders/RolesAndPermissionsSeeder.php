<?php

namespace Database\Seeders;

use App\Support\RolesAndPermissions;
use Illuminate\Database\Seeder;

/**
 * Syncs roles and permissions from App\Enums\Role::permissions().
 * Idempotent. `php artisan migrate` already does this; the seeder remains for
 * running it by hand: php artisan db:seed --class=RolesAndPermissionsSeeder --force
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        RolesAndPermissions::sync();
    }
}
