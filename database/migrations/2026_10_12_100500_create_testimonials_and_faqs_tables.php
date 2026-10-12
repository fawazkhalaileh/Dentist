<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Testimonials and general FAQs are written per language (one row = one locale).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 5);
            // Display name as agreed with the patient (e.g. first name and initial).
            $table->string('author_name', 100);
            $table->text('body');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('consent_confirmed_at')->nullable();
            $table->foreignId('consent_confirmed_by')->nullable()->constrained('users');
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['locale', 'status']);
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 5);
            $table->string('question');
            $table->text('answer');
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['locale', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('testimonials');
    }
};
