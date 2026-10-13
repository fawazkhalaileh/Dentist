<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Prices in minor units (fils). Empty = no price set.
        Schema::table('procedures', function (Blueprint $table) {
            $table->unsignedInteger('price')->nullable()->after('default_duration_minutes');
        });

        // The price agreed for this treatment, copied from the procedure when planned.
        Schema::table('treatment_items', function (Blueprint $table) {
            $table->unsignedInteger('price')->nullable()->after('procedure_id');
        });
    }

    public function down(): void
    {
        Schema::table('treatment_items', fn (Blueprint $table) => $table->dropColumn('price'));
        Schema::table('procedures', fn (Blueprint $table) => $table->dropColumn('price'));
    }
};
