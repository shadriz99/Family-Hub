<?php

declare(strict_types=1);

namespace App\Core\Enums;

enum ReminderDeliveryStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
}
