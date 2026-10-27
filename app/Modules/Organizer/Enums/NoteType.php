<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Enums;

enum NoteType: string
{
    case Text = 'text';
    case Checklist = 'checklist';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Teks Catatan',
            self::Checklist => 'Checklist',
        };
    }
}
