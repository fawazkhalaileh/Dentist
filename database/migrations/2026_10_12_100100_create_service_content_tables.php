<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-locale content blocks for service pages. Bodies are Markdown, rendered
 * with raw HTML stripped (App\Support\Markdown).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('heading');
            $table->text('body');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['service_id', 'locale', 'sort_order']);
        });

        Schema::create('service_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('question');
            $table->text('answer');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['service_id', 'locale', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_faqs');
        Schema::dropIfExists('service_sections');
    }
};
