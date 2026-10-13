<?php

namespace Database\Seeders;

use App\Actions\Procedures\AddStarterProcedures;
use Illuminate\Database\Seeder;

/**
 * General-dentistry procedure catalogue (English and Arabic, with chart colours).
 * Safe to run in any environment and repeatedly: it only adds missing codes and
 * fills empty colours. In production:
 *   php artisan db:seed --class=ProcedureSeeder --force
 * or press "Add starter procedures" in the procedure catalogue (same action).
 */
class ProcedureSeeder extends Seeder
{
    public function run(): void
    {
        app(AddStarterProcedures::class)->handle();
    }
}
