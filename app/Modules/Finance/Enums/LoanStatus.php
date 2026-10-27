<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

enum LoanStatus: string
{
    case Active = 'active';
    case PaidOff = 'paid_off';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('finance.loan_status_active'),
            self::PaidOff => __('finance.loan_status_paid_off'),
            self::Cancelled => __('finance.loan_status_cancelled'),
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Active => 'warning',
            self::PaidOff => 'success',
            self::Cancelled => 'neutral',
        };
    }
}
