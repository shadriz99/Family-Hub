<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

enum AccountType: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case EWallet = 'ewallet';
    case CreditCard = 'credit_card';
    case Savings = 'savings';

    public function label(): string
    {
        return match ($this) {
            self::Cash => __('finance.account_type_cash'),
            self::Bank => __('finance.account_type_bank'),
            self::EWallet => __('finance.account_type_ewallet'),
            self::CreditCard => __('finance.account_type_credit_card'),
            self::Savings => __('finance.account_type_savings'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Cash => 'wallet',
            self::Bank => 'building-library',
            self::EWallet => 'device-phone-mobile',
            self::CreditCard => 'credit-card',
            self::Savings => 'banknotes',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Cash => '#10b981',
            self::Bank => '#3b82f6',
            self::EWallet => '#8b5cf6',
            self::CreditCard => '#f59e0b',
            self::Savings => '#06b6d4',
        };
    }

    public function isSavings(): bool
    {
        return $this === self::Savings;
    }

    public function isOperational(): bool
    {
        return $this !== self::Savings;
    }
}
