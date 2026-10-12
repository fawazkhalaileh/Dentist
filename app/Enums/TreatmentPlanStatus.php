<?php

namespace App\Enums;

enum TreatmentPlanStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Active => __('Active'),
            self::Completed => __('Completed'),
            self::Cancelled => __('Cancelled'),
        };
    }
}
