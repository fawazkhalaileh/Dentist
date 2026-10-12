<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Models\Concerns\AppendOnly;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $appointment_id
 * @property AppointmentStatus|null $from_status
 * @property AppointmentStatus $to_status
 * @property int|null $changed_by
 * @property string|null $reason
 */
#[Fillable(['appointment_id', 'from_status', 'to_status', 'changed_by', 'reason'])]
class AppointmentStatusChange extends Model
{
    use AppendOnly;

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'from_status' => AppointmentStatus::class,
            'to_status' => AppointmentStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
