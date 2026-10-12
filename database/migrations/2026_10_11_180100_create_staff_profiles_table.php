<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained();
            $table->string('slug')->unique();
            $table->string('license_number', 100)->nullable();
            $table->foreignId('photo_media_id')->nullable()->constrained('media')->nullOnDelete();
            // Shown on the public "Meet the dentists" page.
            $table->boolean('is_public')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('staff_profile_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_profile_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('display_name');
            $table->string('specialty')->nullable();
            $table->text('bio')->nullable();
            $table->timestamps();

            $table->unique(['staff_profile_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_profile_translations');
        Schema::dropIfExists('staff_profiles');
    }
};
