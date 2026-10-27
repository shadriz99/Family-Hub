<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

enum Direction: string
{
    case Income = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Income => __('finance.direction_income'),
            self::Expense => __('finance.direction_expense'),
        };
    }
}
