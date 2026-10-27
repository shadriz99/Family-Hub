<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

enum TransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Income => __('finance.transaction_type_income'),
            self::Expense => __('finance.transaction_type_expense'),
            self::Transfer => __('finance.transaction_type_transfer'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Income => '#10b981',
            self::Expense => '#ef4444',
            self::Transfer => '#3b82f6',
        };
    }
}
