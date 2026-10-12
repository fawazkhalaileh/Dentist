<?php

use App\Support\Fdi;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FDI reference table: 32 permanent (11-48) + 20 primary (51-85) teeth.
 * Inserted here rather than in a seeder because it is fixed reference data
 * that treatment items depend on through a foreign key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teeth', function (Blueprint $table) {
            $table->unsignedTinyInteger('fdi_number')->primary();
            $table->string('dentition', 20);
            $table->unsignedTinyInteger('quadrant');
            $table->unsignedTinyInteger('position');
            $table->string('arch', 10);
            $table->string('name', 100);

            $table->index(['dentition', 'quadrant']);
        });

        DB::table('teeth')->insert(Fdi::referenceRows());
    }

    public function down(): void
    {
        Schema::dropIfExists('teeth');
    }
};
