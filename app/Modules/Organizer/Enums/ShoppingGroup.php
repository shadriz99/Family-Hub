<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Enums;

enum ShoppingGroup: string
{
    case Produce = 'produce';
    case MeatSeafood = 'meat_seafood';
    case Groceries = 'groceries';
    case Spices = 'spices';
    case Beverages = 'beverages';
    case Cleaning = 'cleaning';
    case Baby = 'baby';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Produce => 'Sayur & Buah',
            self::MeatSeafood => 'Daging & Ikan',
            self::Groceries => 'Sembako',
            self::Spices => 'Bumbu',
            self::Beverages => 'Minuman',
            self::Cleaning => 'Kebersihan',
            self::Baby => 'Kebutuhan Bayi',
            self::Other => 'Lainnya',
        };
    }
}
