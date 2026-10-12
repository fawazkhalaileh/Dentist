<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => __('Scheduled'),
            self::Confirmed => __('Confirmed'),
            self::CheckedIn => __('Checked in'),
            self::Completed => __('Completed'),
            self::Cancelled => __('Cancelled'),
            self::NoShow => __('No show'),
        };
    }

    /**
     * @return list<AppointmentStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Scheduled => [self::Confirmed, self::CheckedIn, self::Cancelled, self::NoShow],
            self::Confirmed => [self::CheckedIn, self::Cancelled, self::NoShow],
            self::CheckedIn => [self::Completed],
            self::Completed, self::Cancelled, self::NoShow => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /**
     * Statuses that occupy the dentist's time slot (used for conflict checks).
     *
     * @return list<AppointmentStatus>
     */
    public static function blocking(): array
    {
        return [self::Scheduled, self::Confirmed, self::CheckedIn, self::Completed];
    }
}
