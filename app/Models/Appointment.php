<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $patient_id
 * @property int $dentist_id
 * @property int|null $service_id
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property AppointmentStatus $status
 * @property string $source staff|portal|public_request
 * @property string|null $notes
 * @property int|null $created_by
 * @property CarbonImmutable|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property-read Patient $patient
 * @property-read User $dentist
 */
#[Fillable([
    'patient_id', 'dentist_id', 'service_id', 'starts_at', 'ends_at', 'status', 'source',
    'notes', 'created_by', 'cancelled_at', 'cancellation_reason',
])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'status' => AppointmentStatus::class,
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
    public function dentist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return HasMany<AppointmentStatusChange, $this>
     */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(AppointmentStatusChange::class);
    }
}
