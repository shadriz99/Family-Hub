<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

enum LoanDirection: string
{
    case Payable = 'payable';
    case Receivable = 'receivable';

    public function label(): string
    {
        return match ($this) {
            self::Payable => __('finance.loan_direction_payable'),
            self::Receivable => __('finance.loan_direction_receivable'),
        };
    }

    public function isPayable(): bool
    {
        return $this === self::Payable;
    }

    public function isReceivable(): bool
    {
        return $this === self::Receivable;
    }
}
