<?php

namespace App\Actions\Scheduling;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Replaces a dentist's weekly working hours and breaks (clinic-local times).
 */
class SaveWeeklySchedule
{
    /**
     * @param  array<int, list<array{start: string, end: string}>>  $hours  keyed by weekday 0-6
     * @param  array<int, list<array{start: string, end: string, label?: ?string}>>  $breaks
     */
    public function handle(User $dentist, array $hours, array $breaks): void
    {
        $this->assertNoOverlaps($hours, 'hours');
        $this->assertNoOverlaps($breaks, 'breaks');

        DB::transaction(function () use ($dentist, $hours, $breaks) {
            DB::table('working_hours')->where('dentist_id', $dentist->id)->delete();
            DB::table('schedule_breaks')->where('dentist_id', $dentist->id)->delete();
            $now = now();

            foreach ($hours as $weekday => $intervals) {
                foreach ($intervals as $interval) {
                    DB::table('working_hours')->insert(['dentist_id' => $dentist->id, 'weekday' => $weekday, 'starts_at' => $interval['start'], 'ends_at' => $interval['end'], 'created_at' => $now, 'updated_at' => $now]);
                }
            }
            foreach ($breaks as $weekday => $intervals) {
                foreach ($intervals as $interval) {
                    DB::table('schedule_breaks')->insert(['dentist_id' => $dentist->id, 'weekday' => $weekday, 'starts_at' => $interval['start'], 'ends_at' => $interval['end'], 'label' => $interval['label'] ?? null, 'created_at' => $now, 'updated_at' => $now]);
                }
            }

            Audit::record('schedule.updated', $dentist);
        });
    }

    /**
     * @param  array<int, list<array{start: string, end: string}>>  $byDay
     */
    private function assertNoOverlaps(array $byDay, string $field): void
    {
        foreach ($byDay as $weekday => $intervals) {
            usort($intervals, fn ($a, $b) => strcmp($a['start'], $b['start']));
            foreach ($intervals as $i => $interval) {
                if ($interval['start'] >= $interval['end']) {
                    throw ValidationException::withMessages(["{$field}.{$weekday}" => __('Each time range must end after it starts.')]);
                }
                if ($i > 0 && $interval['start'] < $intervals[$i - 1]['end']) {
                    throw ValidationException::withMessages(["{$field}.{$weekday}" => __('Time ranges on the same day must not overlap.')]);
                }
            }
        }
    }
}
