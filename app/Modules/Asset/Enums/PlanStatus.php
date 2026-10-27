<?php

declare(strict_types=1);

namespace App\Modules\Asset\Enums;

enum PlanStatus: string
{
    case Overdue = 'overdue';
    case Due = 'due';
    case Soon = 'soon';
    case Ok = 'ok';

    public function label(): string
    {
        return match ($this) {
            self::Overdue => 'Terlambat',
            self::Due => 'Jatuh Tempo',
            self::Soon => 'Segera',
            self::Ok => 'Aman',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Overdue => 'bg-rose-500/15 text-rose-700 dark:text-rose-400 font-medium',
            self::Due => 'bg-amber-500/15 text-amber-700 dark:text-amber-400 font-medium',
            self::Soon => 'bg-orange-500/15 text-orange-700 dark:text-orange-400',
            self::Ok => 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400',
        };
    }
}
