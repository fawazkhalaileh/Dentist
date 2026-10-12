<?php

namespace App\Support;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\BlockedPeriod;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Free time for a dentist on a clinic-local date: working hours minus breaks,
 * closures (the dentist's and clinic-wide) and appointments that occupy time.
 * Internally everything is minutes since local midnight.
 */
final class Availability
{
    public const STEP_MINUTES = 15;

    /**
     * Start times ("H:i", clinic-local) where an appointment of $minutes fits.
     *
     * @return list<string>
     */
    public static function freeSlots(User $dentist, string $date, int $minutes, ?int $ignoreAppointmentId = null): array
    {
        $working = self::workingIntervals($dentist, $date);
        $busy = self::busyIntervals($dentist, $date, $ignoreAppointmentId);
        $now = ClinicTime::now();
        $earliest = $date === $now->format('Y-m-d') ? $now->hour * 60 + $now->minute : ($date < $now->format('Y-m-d') ? PHP_INT_MAX : 0);

        $slots = [];
        foreach ($working as [$from, $to]) {
            $start = (int) (ceil($from / self::STEP_MINUTES) * self::STEP_MINUTES);
            for ($s = $start; $s + $minutes <= $to; $s += self::STEP_MINUTES) {
                if ($s >= $earliest && ! self::overlapsAny($s, $s + $minutes, $busy)) {
                    $slots[] = sprintf('%02d:%02d', intdiv($s, 60), $s % 60);
                }
            }
        }

        return $slots;
    }

    /**
     * Whether [starts, ends) lies inside the dentist's working hours (breaks excluded).
     */
    public static function withinWorkingHours(User $dentist, CarbonImmutable $startsUtc, CarbonImmutable $endsUtc): bool
    {
        $start = ClinicTime::local($startsUtc);
        $end = ClinicTime::local($endsUtc);
        if ($start->format('Y-m-d') !== $end->format('Y-m-d') && $end->format('H:i') !== '00:00') {
            return false;
        }

        $s = $start->hour * 60 + $start->minute;
        $e = $end->format('H:i') === '00:00' ? 1440 : $end->hour * 60 + $end->minute;
        foreach (self::workingIntervals($dentist, $start->format('Y-m-d')) as [$from, $to]) {
            if ($s >= $from && $e <= $to) {
                return true;
            }
        }

        return false;
    }

    /**
     * Working ranges for the date with breaks cut out, sorted.
     *
     * @return list<array{0: int, 1: int}>
     */
    public static function workingIntervals(User $dentist, string $date): array
    {
        $weekday = ClinicTime::weekday($date);
        $toMinutes = fn (string $time) => ((int) substr($time, 0, 2)) * 60 + (int) substr($time, 3, 2);

        $intervals = DB::table('working_hours')->where('dentist_id', $dentist->id)->where('weekday', $weekday)->orderBy('starts_at')->get()
            ->map(fn ($row) => [$toMinutes($row->starts_at), $toMinutes($row->ends_at)])->all();
        $breaks = DB::table('schedule_breaks')->where('dentist_id', $dentist->id)->where('weekday', $weekday)->get()
            ->map(fn ($row) => [$toMinutes($row->starts_at), $toMinutes($row->ends_at)])->all();

        foreach ($breaks as [$bFrom, $bTo]) {
            $next = [];
            foreach ($intervals as [$from, $to]) {
                if ($bTo <= $from || $bFrom >= $to) {
                    $next[] = [$from, $to];

                    continue;
                }
                if ($bFrom > $from) {
                    $next[] = [$from, $bFrom];
                }
                if ($bTo < $to) {
                    $next[] = [$bTo, $to];
                }
            }
            $intervals = $next;
        }

        usort($intervals, fn ($a, $b) => $a[0] <=> $b[0]);

        return $intervals;
    }

    /**
     * Closures and time-occupying appointments on the date, as local minute ranges.
     *
     * @return list<array{0: int, 1: int}>
     */
    private static function busyIntervals(User $dentist, string $date, ?int $ignoreAppointmentId): array
    {
        $dayStart = ClinicTime::toUtc($date, '00:00');
        $dayEnd = $dayStart->addDay();
        $toLocalMinutes = function (CarbonImmutable $instant) use ($dayStart, $dayEnd): int {
            if ($instant <= $dayStart) {
                return 0;
            }
            if ($instant >= $dayEnd) {
                return 1440;
            }
            $local = ClinicTime::local($instant);

            return $local->hour * 60 + $local->minute;
        };

        $busy = [];
        foreach (self::overlapping(BlockedPeriod::query()->where(fn ($q) => $q->whereNull('dentist_id')->orWhere('dentist_id', $dentist->id)), $dayStart, $dayEnd)->get() as $period) {
            $busy[] = [$toLocalMinutes(CarbonImmutable::instance($period->starts_at)), $toLocalMinutes(CarbonImmutable::instance($period->ends_at))];
        }
        $appointments = Appointment::where('dentist_id', $dentist->id)
            ->whereIn('status', array_map(fn ($s) => $s->value, AppointmentStatus::blocking()))
            ->when($ignoreAppointmentId, fn ($q) => $q->whereKeyNot($ignoreAppointmentId));
        foreach (self::overlapping($appointments, $dayStart, $dayEnd)->get() as $appointment) {
            $busy[] = [$toLocalMinutes(CarbonImmutable::instance($appointment->starts_at)), $toLocalMinutes(CarbonImmutable::instance($appointment->ends_at))];
        }

        return $busy;
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function overlapping($query, CarbonImmutable $startsUtc, CarbonImmutable $endsUtc)
    {
        return $query->where('starts_at', '<', $endsUtc)->where('ends_at', '>', $startsUtc);
    }

    /**
     * @param  list<array{0: int, 1: int}>  $busy
     */
    private static function overlapsAny(int $start, int $end, array $busy): bool
    {
        foreach ($busy as [$from, $to]) {
            if ($start < $to && $end > $from) {
                return true;
            }
        }

        return false;
    }
}
