<?php

namespace App\Enums;

/**
 * Workflow status of a treatment item. Colours are workflow indicators only
 * (not clinical charting conventions) and are always shown with the label.
 */
enum TreatmentStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => __('Planned'),
            self::InProgress => __('In progress'),
            self::Completed => __('Completed'),
            self::Cancelled => __('Cancelled'),
        };
    }

    /**
     * CSS token name defined in resources/css/theme.css (--status-*).
     */
    public function colorToken(): string
    {
        return match ($this) {
            self::Planned => 'planned',
            self::InProgress => 'in-progress',
            self::Completed => 'completed',
            self::Cancelled => 'cancelled',
        };
    }

    /**
     * @return list<TreatmentStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Planned => [self::InProgress, self::Completed, self::Cancelled],
            self::InProgress => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled => [],
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
}
