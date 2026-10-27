<?php

declare(strict_types=1);

namespace App\Modules\Asset\Enums;

enum AssetStatus: string
{
    case Active = 'active';
    case InRepair = 'in_repair';
    case Sold = 'sold';
    case Disposed = 'disposed';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::InRepair => 'Dalam Perbaikan',
            self::Sold => 'Dijual',
            self::Disposed => 'Dibuang',
            self::Lost => 'Hilang',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400',
            self::InRepair => 'bg-amber-500/15 text-amber-700 dark:text-amber-400',
            self::Sold, self::Disposed, self::Lost => 'bg-stone-500/15 text-stone-700 dark:text-stone-400',
        };
    }
}
