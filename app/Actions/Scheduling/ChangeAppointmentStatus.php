<?php

namespace App\Actions\Scheduling;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Moves an appointment through confirmed, checked in, completed, cancelled or
 * no-show. Each change is an append-only appointment_status_changes row.
 */
class ChangeAppointmentStatus
{
    public function handle(Appointment $appointment, AppointmentStatus $to, ?User $actor, ?string $reason = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $to, $actor, $reason) {
            /** @var Appointment $locked */
            $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            if (! $from->canTransitionTo($to)) {
                throw ValidationException::withMessages(['status' => __('A :from appointment cannot be changed to :to.', ['from' => strtolower($from->label()), 'to' => strtolower($to->label())])]);
            }

            $reason = trim((string) $reason) === '' ? null : mb_substr(trim((string) $reason), 0, 255);
            if ($to === AppointmentStatus::Cancelled && $reason === null) {
                throw ValidationException::withMessages(['reason' => __('Give a reason for cancelling.')]);
            }

            $locked->status = $to;
            if ($to === AppointmentStatus::Cancelled) {
                $locked->cancelled_at = now();
                $locked->cancellation_reason = $reason;
            }
            $locked->save();

            $locked->statusChanges()->create(['from_status' => $from, 'to_status' => $to, 'changed_by' => $actor?->id, 'reason' => $reason]);
            Audit::record('appointment.status_changed', $locked, ['from' => $from->value, 'to' => $to->value]);

            return $locked;
        });
    }
}
