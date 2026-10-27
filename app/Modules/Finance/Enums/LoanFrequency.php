<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

enum LoanFrequency: string
{
    case Monthly = 'monthly';
    case Weekly = 'weekly';
    case Once = 'once';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => __('finance.loan_frequency_monthly'),
            self::Weekly => __('finance.loan_frequency_weekly'),
            self::Once => __('finance.loan_frequency_once'),
        };
    }
}
