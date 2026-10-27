<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use App\Core\Models\Household;
use App\Modules\Finance\Enums\LoanDirection;
use App\Modules\Finance\Enums\LoanFrequency;
use App\Modules\Finance\Enums\LoanStatus;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'direction' => LoanDirection::Payable,
            'name' => 'Kredit Motor',
            'counterparty' => 'FIF Finance',
            'principal' => 18000000,
            'installment_count' => 12,
            'installment_amount' => 1750000,
            'total_payable' => 21000000,
            'frequency' => LoanFrequency::Monthly,
            'first_due_date' => now()->startOfMonth()->toDateString(),
            'account_id' => Account::factory(),
            'category_id' => Category::factory(),
            'remind_days_before' => 3,
            'status' => LoanStatus::Active,
        ];
    }
}
