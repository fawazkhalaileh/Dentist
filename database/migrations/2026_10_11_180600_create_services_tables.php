<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public website services. Language-neutral fields live on `services`;
 * per-locale text and SEO slugs live on `service_translations`.
 * Content sections and FAQs are added in Phase 2 with the services CMS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->default('draft')->index();
            $table->string('featured_image_path')->nullable();
            $table->boolean('is_bookable')->default(true);
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('service_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('slug');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('procedure_information')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->timestamps();

            $table->unique(['service_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });

        Schema::create('service_dentist', function (Blueprint $table) {
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dentist_id')->constrained('users');
            $table->primary(['service_id', 'dentist_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_dentist');
        Schema::dropIfExists('service_translations');
        Schema::dropIfExists('services');
    }
};
