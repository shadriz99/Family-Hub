<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Enums;

enum ShoppingListStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Completed => 'Selesai',
            self::Archived => 'Diarsipkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'sky',
            self::Completed => 'emerald',
            self::Archived => 'slate',
        };
    }
}
