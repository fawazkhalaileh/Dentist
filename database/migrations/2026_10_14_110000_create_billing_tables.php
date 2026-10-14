<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invoices and payments. Amounts are minor units (fils). Invoices are issued
 * once and never edited; a mistake is voided (with a reason) and re-issued.
 * Payments are never deleted either; they can be voided with a reason.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            // Derived from the id after insert (see Invoice model), like patient numbers.
            $table->string('number', 32)->unique();
            $table->foreignId('patient_id')->constrained();
            $table->date('issued_on');
            $table->unsignedInteger('total');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users');
            $table->string('void_reason')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'issued_on']);
        });

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained();
            $table->foreignId('treatment_item_id')->nullable()->constrained();
            $table->string('description');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('line_total');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('invoice_id')->nullable()->constrained();
            $table->unsignedInteger('amount');
            $table->string('method', 20);
            $table->string('reference', 100)->nullable();
            $table->date('paid_on');
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->constrained('users');
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users');
            $table->string('void_reason')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'paid_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
    }
};
