<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use App\Core\Models\Household;
use App\Modules\Finance\Enums\AllocationCategory;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'type' => TransactionType::Expense,
            'account_id' => Account::factory(),
            'to_account_id' => null,
            'category_id' => Category::factory(),
            'amount' => 50000,
            'occurred_on' => now()->toDateString(),
            'note' => 'Belanja harian',
            'payee' => 'Warung',
            'allocation' => AllocationCategory::Living,
        ];
    }

    public function income(): static
    {
        return $this->state(fn () => [
            'type' => TransactionType::Income,
            'amount' => 5000000,
            'payee' => 'Kantor',
            'allocation' => null,
        ]);
    }

    public function transfer(): static
    {
        return $this->state(fn () => [
            'type' => TransactionType::Transfer,
            'category_id' => null,
            'amount' => 500000,
            'allocation' => null,
        ]);
    }
}
