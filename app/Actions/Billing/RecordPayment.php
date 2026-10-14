<?php

namespace App\Actions\Billing;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Support\Audit;

/**
 * Records money received. It may be linked to one of the patient's valid
 * invoices, or left unlinked (for example a deposit). Amount in fils.
 */
class RecordPayment
{
    public function handle(Patient $patient, int $amount, PaymentMethod $method, string $paidOn, ?Invoice $invoice, ?string $reference, ?string $notes, User $actor): Payment
    {
        abort_if($amount <= 0, 422);
        abort_if($invoice !== null && ($invoice->patient_id !== $patient->id || $invoice->isVoid()), 422);

        $payment = $patient->payments()->create([
            'invoice_id' => $invoice?->id,
            'amount' => $amount,
            'method' => $method,
            'reference' => $reference,
            'paid_on' => $paidOn,
            'notes' => $notes,
            'received_by' => $actor->id,
        ]);

        Audit::record('billing.payment_recorded', $payment, ['patient_id' => $patient->id, 'amount' => $amount, 'method' => $method->value]);

        return $payment;
    }
}
