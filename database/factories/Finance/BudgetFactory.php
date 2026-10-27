<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use App\Core\Models\Household;
use App\Modules\Finance\Models\Budget;
use App\Modules\Finance\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'category_id' => Category::factory(),
            'period' => now()->startOfMonth()->toDateString(),
            'amount' => 1500000,
        ];
    }
}
