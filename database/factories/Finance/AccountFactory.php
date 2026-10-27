<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use App\Core\Models\Household;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'name' => 'Dompet '.Str::random(4),
            'type' => AccountType::Cash,
            'opening_balance' => 1000000,
            'opening_date' => now()->startOfMonth()->toDateString(),
            'color' => '#10b981',
            'icon' => 'wallet',
            'include_in_total' => true,
            'sort_order' => 0,
        ];
    }

    public function bank(): static
    {
        return $this->state(fn () => [
            'name' => 'Bank BCA '.Str::random(3),
            'type' => AccountType::Bank,
            'color' => '#3b82f6',
            'icon' => 'building-library',
        ]);
    }

    public function savings(): static
    {
        return $this->state(fn () => [
            'name' => 'Tabungan Masa Depan',
            'type' => AccountType::Savings,
            'color' => '#06b6d4',
            'icon' => 'banknotes',
        ]);
    }

    public function creditCard(): static
    {
        return $this->state(fn () => [
            'name' => 'Kartu Kredit Mandiri',
            'type' => AccountType::CreditCard,
            'opening_balance' => -2500000,
            'credit_limit' => 15000000,
            'color' => '#f59e0b',
            'icon' => 'credit-card',
        ]);
    }
}
