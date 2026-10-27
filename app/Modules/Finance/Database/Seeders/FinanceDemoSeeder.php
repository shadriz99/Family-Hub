<?php

declare(strict_types=1);

namespace App\Modules\Finance\Database\Seeders;

use App\Core\Models\Household;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Finance\Actions\SeedDefaultCategories;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Enums\AllocationCategory;
use App\Modules\Finance\Enums\Direction;
use App\Modules\Finance\Enums\LoanDirection;
use App\Modules\Finance\Enums\LoanFrequency;
use App\Modules\Finance\Enums\LoanStatus;
use App\Modules\Finance\Enums\PlannedSource;
use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\AllocationPlan;
use App\Modules\Finance\Models\Budget;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Loan;
use App\Modules\Finance\Models\PlannedItem;
use App\Modules\Finance\Models\RecurringBill;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Database\Seeder;

class FinanceDemoSeeder extends Seeder
{
    public function run(?Household $household = null, ?User $user = null): void
    {
        $household ??= Household::first();

        if ($household === null) {
            return;
        }

        $user ??= $household->members()->first()?->user;
        $userId = $user?->getKey();

        $context = app(HouseholdContext::class);

        $context->runInContext($household, function () use ($household, $user, $userId): void {
            // 1. Ensure categories
            if ($household->categories()->count() === 0) {
                app(SeedDefaultCategories::class)->handle($household, $user);
            }

            // 2. Demo Accounts
            $cash = Account::create([
                'household_id' => $household->getKey(),
                'name' => 'Dompet Tunai',
                'type' => AccountType::Cash,
                'opening_balance' => 750000,
                'opening_date' => now()->startOfMonth()->toDateString(),
                'color' => '#10b981',
                'icon' => 'wallet',
                'include_in_total' => true,
                'sort_order' => 1,
                'created_by' => $userId,
            ]);

            $bca = Account::create([
                'household_id' => $household->getKey(),
                'name' => 'BCA Utama',
                'type' => AccountType::Bank,
                'opening_balance' => 14500000,
                'opening_date' => now()->startOfMonth()->toDateString(),
                'color' => '#3b82f6',
                'icon' => 'building-library',
                'include_in_total' => true,
                'sort_order' => 2,
                'created_by' => $userId,
            ]);

            $mandiri = Account::create([
                'household_id' => $household->getKey(),
                'name' => 'Mandiri Operasional',
                'type' => AccountType::Bank,
                'opening_balance' => 5200000,
                'opening_date' => now()->startOfMonth()->toDateString(),
                'color' => '#0284c7',
                'icon' => 'building-library',
                'include_in_total' => true,
                'sort_order' => 3,
                'created_by' => $userId,
            ]);

            $gopay = Account::create([
                'household_id' => $household->getKey(),
                'name' => 'GoPay',
                'type' => AccountType::EWallet,
                'opening_balance' => 450000,
                'opening_date' => now()->startOfMonth()->toDateString(),
                'color' => '#8b5cf6',
                'icon' => 'device-phone-mobile',
                'include_in_total' => true,
                'sort_order' => 4,
                'created_by' => $userId,
            ]);

            $cc = Account::create([
                'household_id' => $household->getKey(),
                'name' => 'Kartu Kredit BCA',
                'type' => AccountType::CreditCard,
                'opening_balance' => -1800000,
                'opening_date' => now()->startOfMonth()->toDateString(),
                'credit_limit' => 20000000,
                'color' => '#f59e0b',
                'icon' => 'credit-card',
                'include_in_total' => true,
                'sort_order' => 5,
                'created_by' => $userId,
            ]);

            $savings = Account::create([
                'household_id' => $household->getKey(),
                'name' => 'Dana Darurat & Tabungan',
                'type' => AccountType::Savings,
                'opening_balance' => 30000000,
                'opening_date' => now()->startOfMonth()->toDateString(),
                'color' => '#06b6d4',
                'icon' => 'banknotes',
                'include_in_total' => true,
                'sort_order' => 6,
                'created_by' => $userId,
            ]);

            $currentPeriod = now()->startOfMonth()->toDateString();

            // 3. Allocation Plan
            AllocationPlan::create([
                'household_id' => $household->getKey(),
                'period' => $currentPeriod,
                'income_base' => 15000000,
                'living_bps' => 5000,    // 50% = 7.500.000
                'tertiary_bps' => 1500,  // 15% = 2.250.000
                'debt_bps' => 2000,      // 20% = 3.000.000
                'savings_bps' => 1000,   // 10% = 1.500.000
                'social_bps' => 500,     // 5%  =   750.000
                'created_by' => $userId,
            ]);

            // 4. Budgets for 5 Main Categories (Total 100%)
            $catPokok = Category::where('household_id', $household->getKey())
                ->where('name', 'Kebutuhan Pokok')
                ->first();
            $catLifestyle = Category::where('household_id', $household->getKey())
                ->where('name', 'Lifestyle')
                ->first();
            $catTabungan = Category::where('household_id', $household->getKey())
                ->where('name', 'Tabungan')
                ->first();
            $catDonasi = Category::where('household_id', $household->getKey())
                ->where('name', 'Donasi')
                ->first();
            $catHutang = Category::where('household_id', $household->getKey())
                ->where('name', 'Hutang')
                ->first();

            if ($catPokok) {
                Budget::create([
                    'household_id' => $household->getKey(),
                    'category_id' => $catPokok->getKey(),
                    'period' => $currentPeriod,
                    'percentage' => 50,
                    'amount' => 7500000,
                    'created_by' => $userId,
                ]);
            }

            if ($catLifestyle) {
                Budget::create([
                    'household_id' => $household->getKey(),
                    'category_id' => $catLifestyle->getKey(),
                    'period' => $currentPeriod,
                    'percentage' => 20,
                    'amount' => 3000000,
                    'created_by' => $userId,
                ]);
            }

            if ($catTabungan) {
                Budget::create([
                    'household_id' => $household->getKey(),
                    'category_id' => $catTabungan->getKey(),
                    'period' => $currentPeriod,
                    'percentage' => 10,
                    'amount' => 1500000,
                    'created_by' => $userId,
                ]);
            }

            if ($catDonasi) {
                Budget::create([
                    'household_id' => $household->getKey(),
                    'category_id' => $catDonasi->getKey(),
                    'period' => $currentPeriod,
                    'percentage' => 10,
                    'amount' => 1500000,
                    'created_by' => $userId,
                ]);
            }

            if ($catHutang) {
                Budget::create([
                    'household_id' => $household->getKey(),
                    'category_id' => $catHutang->getKey(),
                    'period' => $currentPeriod,
                    'percentage' => 10,
                    'amount' => 1500000,
                    'created_by' => $userId,
                ]);
            }

            // 5. Recurring Bills
            $catGaji = Category::where('household_id', $household->getKey())
                ->where('name', 'Gaji')
                ->first();

            if ($catGaji) {
                RecurringBill::create([
                    'household_id' => $household->getKey(),
                    'name' => 'Gaji Suami',
                    'direction' => Direction::Income,
                    'category_id' => $catGaji->getKey(),
                    'account_id' => $bca->getKey(),
                    'amount' => 12000000,
                    'is_variable_amount' => false,
                    'rrule' => 'FREQ=MONTHLY;BYMONTHDAY=25',
                    'dtstart' => now()->startOfYear()->toDateString(),
                    'remind_days_before' => 2,
                    'is_active' => true,
                    'created_by' => $userId,
                ]);

                RecurringBill::create([
                    'household_id' => $household->getKey(),
                    'name' => 'Gaji Istri',
                    'direction' => Direction::Income,
                    'category_id' => $catGaji->getKey(),
                    'account_id' => $mandiri->getKey(),
                    'amount' => 6000000,
                    'is_variable_amount' => false,
                    'rrule' => 'FREQ=MONTHLY;BYMONTHDAY=28',
                    'dtstart' => now()->startOfYear()->toDateString(),
                    'remind_days_before' => 2,
                    'is_active' => true,
                    'created_by' => $userId,
                ]);
            }

            $catTagihan = Category::where('household_id', $household->getKey())
                ->where('name', 'Tagihan Rumah')
                ->first();

            if ($catTagihan) {
                RecurringBill::create([
                    'household_id' => $household->getKey(),
                    'name' => 'Listrik PLN',
                    'direction' => Direction::Expense,
                    'category_id' => $catTagihan->getKey(),
                    'account_id' => $bca->getKey(),
                    'amount' => 650000,
                    'is_variable_amount' => true,
                    'rrule' => 'FREQ=MONTHLY;BYMONTHDAY=20',
                    'dtstart' => now()->startOfYear()->toDateString(),
                    'remind_days_before' => 3,
                    'is_active' => true,
                    'created_by' => $userId,
                ]);

                RecurringBill::create([
                    'household_id' => $household->getKey(),
                    'name' => 'WiFi Indihome',
                    'direction' => Direction::Expense,
                    'category_id' => $catTagihan->getKey(),
                    'account_id' => $bca->getKey(),
                    'amount' => 375000,
                    'is_variable_amount' => false,
                    'rrule' => 'FREQ=MONTHLY;BYMONTHDAY=15',
                    'dtstart' => now()->startOfYear()->toDateString(),
                    'remind_days_before' => 3,
                    'is_active' => true,
                    'created_by' => $userId,
                ]);
            }

            // 6. Loan: KPR BTN
            $catCicilan = Category::where('household_id', $household->getKey())
                ->where('system_key', 'loan_installment')
                ->first() ?? Category::where('household_id', $household->getKey())
                ->where('name', 'Hutang')
                ->first();

            if ($catCicilan) {
                $loan = Loan::create([
                    'household_id' => $household->getKey(),
                    'direction' => LoanDirection::Payable,
                    'name' => 'KPR BTN Graha Indah',
                    'counterparty' => 'Bank BTN',
                    'principal' => 250000000,
                    'installment_count' => 180,
                    'installment_amount' => 2650000,
                    'total_payable' => 477000000,
                    'frequency' => LoanFrequency::Monthly,
                    'first_due_date' => now()->subMonths(12)->startOfMonth()->addDays(9)->toDateString(),
                    'account_id' => $bca->getKey(),
                    'category_id' => $catCicilan->getKey(),
                    'remind_days_before' => 3,
                    'status' => LoanStatus::Active,
                    'interest_note' => 'Bunga fixed 3 tahun pertama 7.25%',
                    'created_by' => $userId,
                ]);

                // Create planned item for current month
                PlannedItem::create([
                    'household_id' => $household->getKey(),
                    'direction' => Direction::Expense,
                    'name' => 'KPR BTN Graha Indah – Cicilan 13/180',
                    'category_id' => $catCicilan->getKey(),
                    'account_id' => $bca->getKey(),
                    'amount' => 2650000,
                    'paid_amount' => 0,
                    'due_date' => now()->startOfMonth()->addDays(9)->toDateString(),
                    'period' => $currentPeriod,
                    'status' => PlannedStatus::Planned,
                    'source_type' => PlannedSource::Loan,
                    'source_id' => $loan->getKey(),
                    'sequence' => 13,
                    'paid_before_tracking' => false,
                    'created_by' => $userId,
                ]);
            }

            // 7. Sample Transactions
            $subMakan = Category::where('household_id', $household->getKey())
                ->where('name', 'Belanja Dapur')
                ->first() ?? $catPokok;

            if ($subMakan) {
                Transaction::create([
                    'household_id' => $household->getKey(),
                    'type' => TransactionType::Expense,
                    'account_id' => $cash->getKey(),
                    'category_id' => $subMakan->getKey(),
                    'amount' => 125000,
                    'occurred_on' => now()->toDateString(),
                    'note' => 'Sayur, daging ayam, buah',
                    'payee' => 'Pasar Tradisional',
                    'allocation' => AllocationCategory::Living,
                    'created_by' => $userId,
                ]);
            }

            $subBensin = Category::where('household_id', $household->getKey())
                ->where('name', 'Transportasi & Bensin')
                ->first() ?? $catPokok;

            if ($subBensin) {
                Transaction::create([
                    'household_id' => $household->getKey(),
                    'type' => TransactionType::Expense,
                    'account_id' => $gopay->getKey(),
                    'category_id' => $subBensin->getKey(),
                    'amount' => 50000,
                    'occurred_on' => now()->toDateString(),
                    'note' => 'Pertalite motor',
                    'payee' => 'SPBU Pertamina',
                    'allocation' => AllocationCategory::Living,
                    'created_by' => $userId,
                ]);
            }

            // Transfer to savings
            Transaction::create([
                'household_id' => $household->getKey(),
                'type' => TransactionType::Transfer,
                'account_id' => $bca->getKey(),
                'to_account_id' => $savings->getKey(),
                'amount' => 1000000,
                'occurred_on' => now()->toDateString(),
                'note' => 'Alokasi tabungan dana darurat bulanan',
                'allocation' => AllocationCategory::Savings,
                'allocation_detail' => 'Dana Darurat',
                'created_by' => $userId,
            ]);
        });
    }
}
