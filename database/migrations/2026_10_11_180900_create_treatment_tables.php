<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Treatments are relational entities with a lifecycle, not a colour on a tooth.
 *
 * treatment_items   one procedure, optionally on one tooth, with its current status
 * treatment_events  append-only history: every creation and status change
 *
 * Changing a status inserts an event and updates treatment_items.status in the
 * same transaction. Items are cancelled, never deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('dentist_id')->constrained('users');
            $table->string('title');
            $table->string('status', 20)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('treatment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('treatment_plan_id')->nullable()->constrained();
            $table->unsignedTinyInteger('tooth_number')->nullable();
            $table->foreign('tooth_number')->references('fdi_number')->on('teeth');
            $table->foreignId('procedure_id')->constrained();
            $table->foreignId('dentist_id')->constrained('users');
            $table->foreignId('appointment_id')->nullable()->constrained();
            $table->string('status', 20)->index();
            $table->date('planned_for')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['patient_id', 'tooth_number']);
        });

        Schema::create('treatment_item_surfaces', function (Blueprint $table) {
            $table->foreignId('treatment_item_id')->constrained();
            $table->char('surface', 1);
            $table->primary(['treatment_item_id', 'surface']);
        });

        Schema::create('treatment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_item_id')->constrained();
            $table->string('type', 30);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->foreignId('performed_by')->constrained('users');
            $table->text('note')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['treatment_item_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_events');
        Schema::dropIfExists('treatment_item_surfaces');
        Schema::dropIfExists('treatment_items');
        Schema::dropIfExists('treatment_plans');
    }
};
