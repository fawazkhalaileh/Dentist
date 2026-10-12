<?php

namespace App\Models;

use App\Enums\AppointmentRequestStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Unconfirmed booking request from the public site or the portal.
 *
 * @property int $id
 * @property int|null $patient_id
 * @property string $name
 * @property string $phone
 * @property string|null $email
 * @property int|null $preferred_dentist_id
 * @property int|null $service_id
 * @property CarbonImmutable|null $preferred_date
 * @property string|null $preferred_time
 * @property string|null $message
 * @property string $locale
 * @property AppointmentRequestStatus $status
 * @property int|null $appointment_id
 */
#[Fillable([
    'patient_id', 'name', 'phone', 'email', 'preferred_dentist_id', 'service_id',
    'preferred_date', 'preferred_time', 'message', 'locale',
])]
class AppointmentRequest extends Model
{
    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'handled_at' => 'datetime',
            'status' => AppointmentRequestStatus::class,
            // Visitors may describe symptoms: encrypted at rest like other health text.
            'message' => 'encrypted',
        ];
    }

    public const PREFERRED_TIMES = ['any', 'morning', 'afternoon', 'evening'];

    public static function preferredTimeLabel(?string $time): string
    {
        return match ($time) {
            'morning' => __('Morning'),
            'afternoon' => __('Afternoon'),
            'evening' => __('Evening'),
            default => __('Any time'),
        };
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function preferredDentist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'preferred_dentist_id');
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
