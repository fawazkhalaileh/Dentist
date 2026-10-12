<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The clinic works in its own timezone (config clinic.timezone); timestamps are
 * stored in UTC. Weekly hours and breaks are clinic-local times of day.
 */
final class ClinicTime
{
    public static function zone(): string
    {
        return (string) config('clinic.timezone', 'UTC');
    }

    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(self::zone());
    }

    public static function today(): string
    {
        return self::now()->format('Y-m-d');
    }

    /**
     * A clinic-local date and time ("2026-10-14", "09:30") as a UTC instant.
     */
    public static function toUtc(string $date, string $time): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d H:i', "{$date} ".substr($time, 0, 5), self::zone())->utc();
    }

    /**
     * A stored instant shown in the clinic's timezone.
     */
    public static function local(CarbonInterface $instant): CarbonImmutable
    {
        return CarbonImmutable::instance($instant)->setTimezone(self::zone());
    }

    /**
     * Weekday of a clinic-local date: 0 = Sunday ... 6 = Saturday.
     */
    public static function weekday(string $date): int
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $date, self::zone())->dayOfWeek;
    }

    /**
     * Weekdays in display order (Sunday first) with localised names.
     *
     * @return array<int, string>
     */
    public static function weekdays(): array
    {
        $names = [];
        foreach (range(0, 6) as $day) {
            $names[$day] = CarbonImmutable::now()->startOfWeek(CarbonInterface::SUNDAY)->addDays($day)->locale(app()->getLocale())->dayName;
        }

        return $names;
    }
}
