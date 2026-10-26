<?php

declare(strict_types=1);

namespace App\Core\Enums;

enum MemberRole: string
{
    case Owner = 'owner';
    case Partner = 'partner';

    public function color(): string
    {
        return match ($this) {
            self::Owner => '#059669',
            self::Partner => '#7C3AED',
        };
    }
}
