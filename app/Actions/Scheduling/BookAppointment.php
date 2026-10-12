<?php

namespace App\Actions\Scheduling;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\BlockedPeriod;
use App\Models\Patient;
use App\Models\User;
use App\Support\Audit;
use App\Support\Availability;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Books a new appointment or moves an existing one, without double booking.
 *
 * MySQL has no exclusion constraints, so the dentist's user row is locked
 * (SELECT ... FOR UPDATE) to serialise bookings for that dentist; overlaps with
 * appointments and closures are then checked and the row written in the same
 * transaction. Times must be UTC.
 */
class BookAppointment
{
    /**
     * @param  array{service_id: ?int, notes: ?string, source: string}  $details
     */
    public function handle(
        Patient $patient,
        User $dentist,
        CarbonImmutable $startsUtc,
        CarbonImmutable $endsUtc,
        array $details,
        User $actor,
        bool $allowOutsideHours = false,
        ?Appointment $moving = null,
    ): Appointment {
        abort_unless($startsUtc->utcOffset() === 0 && $endsUtc->utcOffset() === 0, 500, 'Appointment times must be UTC.');

        if ($endsUtc <= $startsUtc) {
            throw ValidationException::withMessages(['time' => __('The appointment must end after it starts.')]);
        }

        return DB::transaction(function () use ($patient, $dentist, $startsUtc, $endsUtc, $details, $actor, $allowOutsideHours, $moving) {
            User::whereKey($dentist->id)->lockForUpdate()->firstOrFail();

            $clash = Availability::overlapping(
                Appointment::where('dentist_id', $dentist->id)->whereIn('status', array_map(fn ($s) => $s->value, AppointmentStatus::blocking())),
                $startsUtc, $endsUtc,
            )->when($moving, fn ($q) => $q->whereKeyNot($moving->id))->exists();
            if ($clash) {
                throw ValidationException::withMessages(['time' => __('The dentist already has an appointment at this time.')]);
            }

            $closed = Availability::overlapping(
                BlockedPeriod::where(fn ($q) => $q->whereNull('dentist_id')->orWhere('dentist_id', $dentist->id)),
                $startsUtc, $endsUtc,
            )->exists();
            if ($closed) {
                throw ValidationException::withMessages(['time' => __('The clinic or the dentist is closed at this time.')]);
            }

            if (! $allowOutsideHours && ! Availability::withinWorkingHours($dentist, $startsUtc, $endsUtc)) {
                throw ValidationException::withMessages(['time' => __('This is outside the dentist\'s working hours. Tick “Book outside working hours” to book anyway.')]);
            }

            if ($moving) {
                $from = [$moving->starts_at->toIso8601String(), $moving->dentist_id];
                $moving->update(['dentist_id' => $dentist->id, 'starts_at' => $startsUtc, 'ends_at' => $endsUtc]);
                Audit::record('appointment.rescheduled', $moving, ['from' => $from[0], 'to' => $startsUtc->toIso8601String(), 'dentist_changed' => $from[1] !== $dentist->id ? true : null]);

                return $moving;
            }

            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'dentist_id' => $dentist->id,
                'service_id' => $details['service_id'],
                'starts_at' => $startsUtc,
                'ends_at' => $endsUtc,
                'status' => AppointmentStatus::Scheduled,
                'source' => $details['source'],
                'notes' => $details['notes'],
                'created_by' => $actor->id,
            ]);
            $appointment->statusChanges()->create(['to_status' => AppointmentStatus::Scheduled, 'changed_by' => $actor->id]);
            Audit::record('appointment.booked', $appointment);

            return $appointment;
        });
    }
}
