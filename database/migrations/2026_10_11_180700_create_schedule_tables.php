<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Weekly recurring working hours per dentist. weekday: 0 = Sunday ... 6 = Saturday.
        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dentist_id')->constrained('users');
            $table->unsignedTinyInteger('weekday');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestamps();

            $table->unique(['dentist_id', 'weekday', 'starts_at']);
        });

        // Recurring breaks inside working hours (e.g. lunch).
        Schema::create('schedule_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dentist_id')->constrained('users');
            $table->unsignedTinyInteger('weekday');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('label', 100)->nullable();
            $table->timestamps();

            $table->index(['dentist_id', 'weekday']);
        });

        // One-off unavailability. dentist_id NULL = whole clinic (holiday, closure).
        Schema::create('blocked_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dentist_id')->nullable()->constrained('users');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['dentist_id', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_periods');
        Schema::dropIfExists('schedule_breaks');
        Schema::dropIfExists('working_hours');
    }
};
