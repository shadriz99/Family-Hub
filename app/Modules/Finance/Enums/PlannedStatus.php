<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

enum PlannedStatus: string
{
    case Planned = 'planned';
    case Partial = 'partial';
    case Paid = 'paid';
    case Skipped = 'skipped';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => __('finance.planned_status_planned'),
            self::Partial => __('finance.planned_status_partial'),
            self::Paid => __('finance.planned_status_paid'),
            self::Skipped => __('finance.planned_status_skipped'),
            self::Cancelled => __('finance.planned_status_cancelled'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Planned => 'neutral',
            self::Partial => 'warning',
            self::Paid => 'success',
            self::Skipped => 'slate',
            self::Cancelled => 'danger',
        };
    }
}
