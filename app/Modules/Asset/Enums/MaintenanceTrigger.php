<?php

declare(strict_types=1);

namespace App\Modules\Asset\Enums;

enum MaintenanceTrigger: string
{
    case Time = 'time';
    case Meter = 'meter';
    case TimeOrMeter = 'time_or_meter';

    public function label(): string
    {
        return match ($this) {
            self::Time => 'Waktu',
            self::Meter => 'Meter',
            self::TimeOrMeter => 'Waktu atau Meter',
        };
    }
}
