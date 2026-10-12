<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Internal procedure catalogue used in the dental chart and treatment plans.
        Schema::create('procedures', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('category', 50)->nullable()->index();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('default_duration_minutes')->default(30);
            // tooth = needs a tooth, surface = tooth + surfaces, mouth = whole mouth
            $table->string('scope', 20)->default('tooth');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedures');
    }
};
