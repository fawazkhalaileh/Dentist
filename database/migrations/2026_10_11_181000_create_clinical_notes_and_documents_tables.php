<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Notes are never edited. A correction is a new note pointing at the one it amends.
        Schema::create('clinical_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('author_id')->constrained('users');
            $table->foreignId('treatment_item_id')->nullable()->constrained();
            $table->foreignId('appointment_id')->nullable()->constrained();
            $table->foreignId('amends_id')->nullable()->constrained('clinical_notes');
            $table->text('body');
            $table->timestamps();

            $table->index(['patient_id', 'created_at']);
        });

        // Files are stored on a private disk and only served through authorised routes.
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained();
            $table->nullableMorphs('documentable');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('category', 50)->nullable();
            $table->string('disk', 30);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('clinical_notes');
    }
};
