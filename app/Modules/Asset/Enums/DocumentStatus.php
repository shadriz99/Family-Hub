<?php

declare(strict_types=1);

namespace App\Modules\Asset\Enums;

enum DocumentStatus: string
{
    case Active = 'active';
    case Superseded = 'superseded';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Superseded => 'Digantikan',
            self::Expired => 'Kedaluwarsa',
        };
    }
}
