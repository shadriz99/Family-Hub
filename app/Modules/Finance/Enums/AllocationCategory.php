<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

enum AllocationCategory: string
{
    case Living = 'living';
    case Tertiary = 'tertiary';
    case Debt = 'debt';
    case Savings = 'savings';
    case Social = 'social';

    public function label(): string
    {
        return match ($this) {
            self::Living => __('finance.allocation_living'),
            self::Tertiary => __('finance.allocation_tertiary'),
            self::Debt => __('finance.allocation_debt'),
            self::Savings => __('finance.allocation_savings'),
            self::Social => __('finance.allocation_social'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Living => '#10b981',   // Emerald
            self::Tertiary => '#8b5cf6', // Violet
            self::Debt => '#ef4444',     // Red/Coral
            self::Savings => '#3b82f6',  // Blue
            self::Social => '#f59e0b',   // Amber
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Living => __('finance.allocation_desc_living'),
            self::Tertiary => __('finance.allocation_desc_tertiary'),
            self::Debt => __('finance.allocation_desc_debt'),
            self::Savings => __('finance.allocation_desc_savings'),
            self::Social => __('finance.allocation_desc_social'),
        };
    }
}
