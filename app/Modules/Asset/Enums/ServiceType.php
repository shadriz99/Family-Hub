<?php

declare(strict_types=1);

namespace App\Modules\Asset\Enums;

enum ServiceType: string
{
    case Preventive = 'preventive';
    case Repair = 'repair';
    case Inspection = 'inspection';

    public function label(): string
    {
        return match ($this) {
            self::Preventive => 'Perawatan Rutin',
            self::Repair => 'Perbaikan',
            self::Inspection => 'Pemeriksaan',
        };
    }
}
