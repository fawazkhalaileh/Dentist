<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MySQL has no exclusion constraints, so double-booking is prevented in the
 * booking action: lock the dentist row (SELECT ... FOR UPDATE), re-check for
 * overlaps on (dentist_id, starts_at, ends_at), then insert, all in one transaction.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('dentist_id')->constrained('users');
            $table->foreignId('service_id')->nullable()->constrained();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 20)->index();
            // staff | portal | public_request
            $table->string('source', 20);
            // Administrative notes only. Clinical content belongs in clinical_notes.
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['dentist_id', 'starts_at', 'ends_at']);
            $table->index(['patient_id', 'starts_at']);
        });

        Schema::create('appointment_status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('changed_by')->nullable()->constrained('users');
            $table->string('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Unconfirmed requests (public website or portal). Staff approve them into appointments.
        Schema::create('appointment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->nullable()->constrained();
            $table->string('name');
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->foreignId('preferred_dentist_id')->nullable()->constrained('users');
            $table->foreignId('service_id')->nullable()->constrained();
            $table->date('preferred_date')->nullable();
            $table->string('preferred_time', 20)->nullable();
            $table->text('message')->nullable();
            $table->string('locale', 5)->default('en');
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('handled_by')->nullable()->constrained('users');
            $table->timestamp('handled_at')->nullable();
            $table->foreignId('appointment_id')->nullable()->constrained();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_requests');
        Schema::dropIfExists('appointment_status_changes');
        Schema::dropIfExists('appointments');
    }
};
