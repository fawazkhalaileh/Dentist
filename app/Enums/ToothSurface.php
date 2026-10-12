<?php

namespace App\Enums;

enum ToothSurface: string
{
    case Mesial = 'M';
    case Occlusal = 'O';
    case Distal = 'D';
    case Buccal = 'B';
    case Lingual = 'L';
    case Incisal = 'I';

    public function label(): string
    {
        return match ($this) {
            self::Mesial => __('Mesial'),
            self::Occlusal => __('Occlusal'),
            self::Distal => __('Distal'),
            self::Buccal => __('Buccal / Facial'),
            self::Lingual => __('Lingual / Palatal'),
            self::Incisal => __('Incisal'),
        };
    }
}
