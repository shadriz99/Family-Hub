<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

enum CategoryType: string
{
    case Income = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Income => __('finance.category_type_income'),
            self::Expense => __('finance.category_type_expense'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Income => '#10b981',
            self::Expense => '#ef4444',
        };
    }
}
