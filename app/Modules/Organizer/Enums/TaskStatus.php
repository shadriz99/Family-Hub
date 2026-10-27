<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Enums;

enum TaskStatus: string
{
    case Open = 'open';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Perlu Dilakukan',
            self::Done => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'amber',
            self::Done => 'emerald',
            self::Cancelled => 'slate',
        };
    }
}
