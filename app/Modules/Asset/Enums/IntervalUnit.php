<?php

declare(strict_types=1);

namespace App\Modules\Asset\Enums;

enum IntervalUnit: string
{
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Year = 'year';

    public function label(): string
    {
        return match ($this) {
            self::Day => 'Hari',
            self::Week => 'Minggu',
            self::Month => 'Bulan',
            self::Year => 'Tahun',
        };
    }
}
