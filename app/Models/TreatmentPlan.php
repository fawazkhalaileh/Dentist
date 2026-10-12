<?php

namespace App\Models;

use App\Enums\TreatmentPlanStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $patient_id
 * @property int $dentist_id
 * @property string $title
 * @property TreatmentPlanStatus $status
 * @property string|null $notes
 * @property int $created_by
 */
#[Fillable(['patient_id', 'dentist_id', 'title', 'status', 'notes', 'created_by'])]
class TreatmentPlan extends Model
{
    protected function casts(): array
    {
        return [
            'status' => TreatmentPlanStatus::class,
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
     * @return HasMany<TreatmentItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TreatmentItem::class);
    }
}
