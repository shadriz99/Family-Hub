<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use App\Core\Models\Household;
use App\Modules\Finance\Models\AllocationPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AllocationPlan>
 */
class AllocationPlanFactory extends Factory
{
    protected $model = AllocationPlan::class;

    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'period' => now()->startOfMonth()->toDateString(),
            'income_base' => 10000000,
            'living_bps' => 5000,    // 50%
            'tertiary_bps' => 1500,  // 15%
            'debt_bps' => 2000,      // 20%
            'savings_bps' => 1000,   // 10%
            'social_bps' => 500,     // 5%
        ];
    }
}
