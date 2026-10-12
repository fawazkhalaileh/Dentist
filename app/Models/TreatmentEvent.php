<?php

namespace App\Models;

use App\Enums\TreatmentEventType;
use App\Enums\TreatmentStatus;
use App\Models\Concerns\AppendOnly;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Immutable lifecycle entry for a treatment item.
 *
 * @property int $id
 * @property int $treatment_item_id
 * @property TreatmentEventType $type
 * @property TreatmentStatus|null $from_status
 * @property TreatmentStatus|null $to_status
 * @property int $performed_by
 * @property string|null $note
 * @property Carbon $occurred_at
 */
#[Fillable(['treatment_item_id', 'type', 'from_status', 'to_status', 'performed_by', 'note', 'occurred_at'])]
class TreatmentEvent extends Model
{
    use AppendOnly;

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'type' => TreatmentEventType::class,
            'from_status' => TreatmentStatus::class,
            'to_status' => TreatmentStatus::class,
            'occurred_at' => 'datetime',
            'note' => 'encrypted',
        ];
    }

    /**
     * @return BelongsTo<TreatmentItem, $this>
     */
    public function treatmentItem(): BelongsTo
    {
        return $this->belongsTo(TreatmentItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
