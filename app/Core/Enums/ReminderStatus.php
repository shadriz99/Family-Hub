<?php

declare(strict_types=1);

namespace App\Core\Enums;

enum ReminderStatus: string
{
    case Scheduled = 'scheduled';
    case Cancelled = 'cancelled';
}
