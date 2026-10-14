<?php

namespace App\Actions\Billing;

use App\Enums\TreatmentStatus;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Patient;
use App\Models\TreatmentItem;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Issues an invoice. Treatment lines must belong to the patient, must not be
 * cancelled, and must not already be on a valid invoice (rows are locked, so
 * two people cannot bill the same treatment at once). Amounts in fils.
 */
class CreateInvoice
{
    /**
     * @param  list<array{treatment_item_id: ?int, description: string, quantity: int, unit_price: int}>  $lines
     */
    public function handle(Patient $patient, array $lines, string $issuedOn, ?string $notes, User $actor): Invoice
    {
        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => __('Add at least one line.')]);
        }

        return DB::transaction(function () use ($patient, $lines, $issuedOn, $notes, $actor) {
            $itemIds = array_values(array_filter(array_column($lines, 'treatment_item_id')));
            if ($itemIds !== []) {
                $items = TreatmentItem::whereKey($itemIds)->lockForUpdate()->get();
                abort_if($items->count() !== count(array_unique($itemIds)) || $items->contains(fn ($i) => $i->patient_id !== $patient->id), 422);
                abort_if($items->contains(fn ($i) => $i->status === TreatmentStatus::Cancelled), 422);

                $alreadyBilled = InvoiceLine::whereIn('treatment_item_id', $itemIds)
                    ->whereHas('invoice', fn ($q) => $q->whereNull('voided_at'))->exists();
                if ($alreadyBilled) {
                    throw ValidationException::withMessages(['lines' => __('One of these treatments is already on an invoice.')]);
                }
            }

            $prepared = array_map(fn (array $line) => [
                'treatment_item_id' => $line['treatment_item_id'] ?: null,
                'description' => mb_substr(trim($line['description']), 0, 255),
                'quantity' => max(1, $line['quantity']),
                'unit_price' => max(0, $line['unit_price']),
                'line_total' => max(1, $line['quantity']) * max(0, $line['unit_price']),
            ], $lines);

            $invoice = Invoice::create([
                'patient_id' => $patient->id,
                'issued_on' => $issuedOn,
                'total' => array_sum(array_column($prepared, 'line_total')),
                'notes' => $notes,
                'created_by' => $actor->id,
            ]);
            foreach ($prepared as $line) {
                $invoice->lines()->create($line);
            }

            Audit::record('billing.invoice_created', $invoice, ['patient_id' => $patient->id, 'total' => $invoice->total]);

            return $invoice->refresh();
        });
    }
}
