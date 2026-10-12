<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "Asked, and the patient has no allergies" is a different clinical fact
        // from "not recorded", which an empty allergies field cannot express.
        Schema::table('medical_histories', function (Blueprint $table) {
            $table->boolean('no_known_allergies')->default(false)->after('allergies');
        });
    }

    public function down(): void
    {
        Schema::table('medical_histories', function (Blueprint $table) {
            $table->dropColumn('no_known_allergies');
        });
    }
};
