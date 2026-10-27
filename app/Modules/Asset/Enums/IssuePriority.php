<?php

declare(strict_types=1);

namespace App\Modules\Asset\Enums;

enum IssuePriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Rendah',
            self::Medium => 'Sedang',
            self::Urgent => 'Mendesak',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Low => 'bg-stone-500/15 text-stone-700 dark:text-stone-300',
            self::Medium => 'bg-amber-500/15 text-amber-700 dark:text-amber-400',
            self::Urgent => 'bg-rose-500/15 text-rose-700 dark:text-rose-400 font-semibold',
        };
    }
}
