<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Enums;

enum TaskReminderOffset: string
{
    case AtDue = 'at_due';
    case OneHourBefore = '1h_before';
    case OneDayBefore0800 = '1d_before_0800';

    public function label(): string
    {
        return match ($this) {
            self::AtDue => 'Saat jatuh tempo',
            self::OneHourBefore => '1 jam sebelum',
            self::OneDayBefore0800 => '1 hari sebelum (08:00)',
        };
    }
}
