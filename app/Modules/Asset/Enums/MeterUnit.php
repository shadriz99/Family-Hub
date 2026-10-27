<?php

declare(strict_types=1);

namespace App\Modules\Asset\Enums;

enum MeterUnit: string
{
    case Km = 'km';
    case Hour = 'hour';

    public function label(): string
    {
        return match ($this) {
            self::Km => 'km',
            self::Hour => 'jam',
        };
    }
}
