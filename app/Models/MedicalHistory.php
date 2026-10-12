<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One version of a patient's medical history. New reviews create new rows.
 *
 * @property int $id
 * @property int $patient_id
 * @property int $recorded_by
 * @property string|null $allergies
 * @property string|null $medications
 * @property string|null $conditions
 * @property string|null $notes
 */
#[Fillable(['patient_id', 'recorded_by', 'allergies', 'medications', 'conditions', 'notes'])]
class MedicalHistory extends Model
{
    protected function casts(): array
    {
        return [
            'allergies' => 'encrypted',
            'medications' => 'encrypted',
            'conditions' => 'encrypted',
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
