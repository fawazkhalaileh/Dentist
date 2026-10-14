<?php

namespace App\Actions\Billing;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Validation\ValidationException;

/**
 * Voids an invoice or a payment, with a reason. The record stays visible and
 * simply stops counting towards the patient's balance.
 */
class VoidBillingRecord
{
    public function handle(Invoice|Payment $record, string $reason, User $actor): void
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['voidReason' => __('Give a reason.')]);
        }
        abort_if($record->isVoid(), 422);

        $record->forceFill(['voided_at' => now(), 'voided_by' => $actor->id, 'void_reason' => mb_substr($reason, 0, 255)])->save();

        Audit::record($record instanceof Invoice ? 'billing.invoice_voided' : 'billing.payment_voided', $record, ['patient_id' => $record->patient_id]);
    }
}
