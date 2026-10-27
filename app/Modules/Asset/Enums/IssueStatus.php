<?php

declare(strict_types=1);

namespace App\Modules\Asset\Enums;

enum IssueStatus: string
{
    case Open = 'open';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Terbuka',
            self::Scheduled => 'Terjadwal',
            self::InProgress => 'Sedang Dikerjakan',
            self::Resolved => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Open => 'bg-rose-500/15 text-rose-700 dark:text-rose-400',
            self::Scheduled => 'bg-cyan-500/15 text-cyan-700 dark:text-cyan-400',
            self::InProgress => 'bg-amber-500/15 text-amber-700 dark:text-amber-400',
            self::Resolved => 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400',
            self::Cancelled => 'bg-stone-500/15 text-stone-700 dark:text-stone-400',
        };
    }
}
