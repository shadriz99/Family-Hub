<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use App\Core\Models\Household;
use App\Modules\Finance\Enums\Direction;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\RecurringBill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringBill>
 */
class RecurringBillFactory extends Factory
{
    protected $model = RecurringBill::class;

    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'name' => 'Listrik PLN',
            'direction' => Direction::Expense,
            'category_id' => Category::factory(),
            'account_id' => Account::factory(),
            'amount' => 500000,
            'is_variable_amount' => true,
            'rrule' => 'FREQ=MONTHLY;BYMONTHDAY=20',
            'dtstart' => now()->startOfMonth()->toDateString(),
            'remind_days_before' => 3,
            'is_active' => true,
        ];
    }
}
