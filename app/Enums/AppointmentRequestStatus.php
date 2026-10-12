<?php

namespace App\Enums;

enum AppointmentRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Declined = 'declined';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Approved => __('Approved'),
            self::Declined => __('Declined'),
            self::Withdrawn => __('Withdrawn'),
        };
    }
}
