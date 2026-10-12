<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Clinical notes are never edited; a correction is a new note with amends_id.
 *
 * @property int $id
 * @property int $patient_id
 * @property int $author_id
 * @property int|null $treatment_item_id
 * @property int|null $appointment_id
 * @property int|null $amends_id
 * @property string $body
 * @property-read Patient $patient
 */
#[Fillable(['patient_id', 'author_id', 'treatment_item_id', 'appointment_id', 'amends_id', 'body'])]
class ClinicalNote extends Model
{
    use AppendOnly;

    protected function casts(): array
    {
        return [
            'body' => 'encrypted',
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
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return BelongsTo<ClinicalNote, $this>
     */
    public function amends(): BelongsTo
    {
        return $this->belongsTo(ClinicalNote::class, 'amends_id');
    }
}
