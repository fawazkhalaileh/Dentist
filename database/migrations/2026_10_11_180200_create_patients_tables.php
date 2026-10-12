<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            // Derived from the id after insert (see Patient model), so it is unique without race conditions.
            $table->string('patient_number', 32)->unique();
            // Portal login, if the patient has one. One account per patient record.
            $table->foreignId('user_id')->nullable()->unique()->constrained();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('date_of_birth')->nullable();
            $table->string('sex', 20)->nullable();
            $table->string('phone', 30)->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('address_line')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->string('preferred_locale', 5)->default('en');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            // Patients are archived, never hard-deleted.
            $table->softDeletes();

            $table->index(['last_name', 'first_name']);
        });

        // Care team: dentists assigned to a patient. Drives dentist access to records.
        Schema::create('patient_dentist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('dentist_id')->constrained('users');
            $table->boolean('is_primary')->default(false);
            $table->foreignId('assigned_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['patient_id', 'dentist_id']);
            $table->index('dentist_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_dentist');
        Schema::dropIfExists('patients');
    }
};
