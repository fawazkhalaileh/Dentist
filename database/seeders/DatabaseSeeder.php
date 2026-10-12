<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Reference data runs everywhere; synthetic demo data only locally.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            ProcedureSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call([DemoSeeder::class, DemoContentSeeder::class]);
        }
    }
}
