<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Enums;

enum EventKind: string
{
    case Event = 'event';
    case Birthday = 'birthday';
    case Anniversary = 'anniversary';

    public function label(): string
    {
        return match ($this) {
            self::Event => 'Acara Keluarga',
            self::Birthday => 'Ulang Tahun',
            self::Anniversary => 'Anniversary',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Event => 'calendar',
            self::Birthday => 'cake',
            self::Anniversary => 'heart',
        };
    }
}
