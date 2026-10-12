<?php

namespace Database\Seeders;

use App\Actions\Procedures\AddStarterProcedures;
use Illuminate\Database\Seeder;

/**
 * Starter procedure catalogue for local development. In production an
 * administrator adds it from the procedure catalogue screen (same action).
 */
class ProcedureSeeder extends Seeder
{
    public function run(): void
    {
        app(AddStarterProcedures::class)->handle();
    }
}
