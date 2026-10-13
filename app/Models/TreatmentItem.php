<?php

namespace App\Models;

use App\Enums\ToothSurface;
use App\Enums\TreatmentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\TreatmentItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One procedure for a patient, optionally on one tooth. Its full lifecycle is
 * kept in TreatmentEvent rows; `status` is the current state only.
 * Status changes must go through the treatment actions (Phase 4), which write
 * the event and the new status in one transaction.
 *
 * @property int $id
 * @property int $patient_id
 * @property int|null $treatment_plan_id
 * @property int|null $tooth_number
 * @property int $procedure_id
 * @property int $dentist_id
 * @property int|null $appointment_id
 * @property TreatmentStatus $status
 * @property CarbonImmutable|null $planned_for
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $cancelled_at
 * @property string|null $notes
 * @property int $created_by
 * @property-read Patient $patient
 */
#[Fillable([
    'patient_id', 'treatment_plan_id', 'tooth_number', 'procedure_id', 'price', 'dentist_id',
    'appointment_id', 'status', 'planned_for', 'started_at', 'completed_at', 'cancelled_at',
    'notes', 'created_by',
])]
class TreatmentItem extends Model
{
    /** @use HasFactory<TreatmentItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => TreatmentStatus::class,
            'price' => 'integer',
            'planned_for' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'notes' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function () {
            throw new \LogicException('Treatment items are cancelled, never deleted.');
        });
    }

    /**
     * Surface letters in the conventional order (M O D B L I), e.g. "MOD".
     */
    public function surfaceCodes(): string
    {
        $order = array_map(fn (ToothSurface $s) => $s->value, ToothSurface::cases());
        $codes = $this->surfaces->map(fn (TreatmentItemSurface $s) => $s->surface->value)->all();
        usort($codes, fn (string $a, string $b) => array_search($a, $order, true) <=> array_search($b, $order, true));

        return implode('', $codes);
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<Tooth, $this>
     */
    public function tooth(): BelongsTo
    {
        return $this->belongsTo(Tooth::class, 'tooth_number', 'fdi_number');
    }

    /**
     * @return BelongsTo<Procedure, $this>
     */
    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function dentist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }

    /**
     * @return BelongsTo<TreatmentPlan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class, 'treatment_plan_id');
    }

    /**
     * @return HasMany<TreatmentItemSurface, $this>
     */
    public function surfaces(): HasMany
    {
        return $this->hasMany(TreatmentItemSurface::class);
    }

    /**
     * @return HasMany<TreatmentEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(TreatmentEvent::class)->orderBy('occurred_at')->orderBy('id');
    }
}
