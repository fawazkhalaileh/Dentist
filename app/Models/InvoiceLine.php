<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $invoice_id
 * @property int|null $treatment_item_id
 * @property string $description
 * @property int $quantity
 * @property int $unit_price
 * @property int $line_total
 */
#[Fillable(['invoice_id', 'treatment_item_id', 'description', 'quantity', 'unit_price', 'line_total'])]
class InvoiceLine extends Model
{
    use AppendOnly;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'line_total' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<TreatmentItem, $this>
     */
    public function treatmentItem(): BelongsTo
    {
        return $this->belongsTo(TreatmentItem::class);
    }
}
