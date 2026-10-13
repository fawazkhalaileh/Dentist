<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Colour used on the dental chart (#rrggbb). Empty = the category's default colour.
        Schema::table('procedures', function (Blueprint $table) {
            $table->string('color', 7)->nullable()->after('scope');
        });
    }

    public function down(): void
    {
        Schema::table('procedures', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
