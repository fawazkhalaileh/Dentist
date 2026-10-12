<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Before/after photos are patient images: an item can only be published once
 * staff record that the patient's written consent was obtained.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gallery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('before_media_id')->constrained('media');
            $table->foreignId('after_media_id')->constrained('media');
            $table->timestamp('consent_confirmed_at')->nullable();
            $table->foreignId('consent_confirmed_by')->nullable()->constrained('users');
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('gallery_item_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gallery_item_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['gallery_item_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_item_translations');
        Schema::dropIfExists('gallery_items');
    }
};
