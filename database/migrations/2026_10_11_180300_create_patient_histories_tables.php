<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Medical and dental histories are versioned: each review inserts a new row,
 * the latest row is current, and earlier rows are kept. Free-text fields are
 * encrypted at the application layer (see model casts), hence TEXT columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('recorded_by')->constrained('users');
            $table->text('allergies')->nullable();
            $table->text('medications')->nullable();
            $table->text('conditions')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'created_at']);
        });

        Schema::create('dental_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('recorded_by')->constrained('users');
            $table->text('chief_complaint')->nullable();
            $table->text('previous_treatments')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_histories');
        Schema::dropIfExists('medical_histories');
    }
};
