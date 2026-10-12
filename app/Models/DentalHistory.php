<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One version of a patient's dental history. New reviews create new rows.
 *
 * @property int $id
 * @property int $patient_id
 * @property int $recorded_by
 * @property string|null $chief_complaint
 * @property string|null $previous_treatments
 * @property string|null $notes
 */
#[Fillable(['patient_id', 'recorded_by', 'chief_complaint', 'previous_treatments', 'notes'])]
class DentalHistory extends Model
{
    use AppendOnly;

    protected function casts(): array
    {
        return [
            'chief_complaint' => 'encrypted',
            'previous_treatments' => 'encrypted',
            'notes' => 'encrypted',
        ];
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
