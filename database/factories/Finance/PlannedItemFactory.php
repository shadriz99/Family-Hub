<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use App\Core\Models\Household;
use App\Modules\Finance\Enums\Direction;
use App\Modules\Finance\Enums\PlannedSource;
use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\PlannedItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlannedItem>
 */
class PlannedItemFactory extends Factory
{
    protected $model = PlannedItem::class;

    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'direction' => Direction::Expense,
            'name' => 'KPR BTN – Cicilan 1/180',
            'category_id' => Category::factory(),
            'account_id' => Account::factory(),
            'amount' => 2500000,
            'paid_amount' => 0,
            'due_date' => now()->startOfMonth()->addDays(9)->toDateString(),
            'period' => now()->startOfMonth()->toDateString(),
            'status' => PlannedStatus::Planned,
            'source_type' => PlannedSource::Loan,
        ];
    }
}
