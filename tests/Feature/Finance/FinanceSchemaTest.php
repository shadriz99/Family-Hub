<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Core\Actions\CreateHousehold;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Enums\AllocationCategory;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Enums\Direction;
use App\Modules\Finance\Enums\LoanDirection;
use App\Modules\Finance\Enums\LoanFrequency;
use App\Modules\Finance\Enums\LoanStatus;
use App\Modules\Finance\Enums\PlannedSource;
use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\AllocationPlan;
use App\Modules\Finance\Models\Budget;
use App\Modules\Finance\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::create([
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'password' => bcrypt('password123'),
    ]);

    $this->household = app(CreateHousehold::class)->handle(
        $this->user,
        'Keluarga Budi',
        1,
        'Asia/Jakarta'
    );
});

it('creates all 8 finance tables with correct structure and indexes', function (): void {
    $tables = [
        'fin_accounts',
        'fin_categories',
        'fin_recurring_bills',
        'fin_loans',
        'fin_planned_items',
        'fin_transactions',
        'fin_budgets',
        'fin_allocation_plans',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Table {$table} should exist");
    }

    expect(Schema::hasColumns('fin_accounts', [
        'id', 'household_id', 'name', 'type', 'opening_balance', 'opening_date',
        'credit_limit', 'color', 'icon', 'include_in_total', 'sort_order', 'archived_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('fin_categories', [
        'id', 'household_id', 'parent_id', 'name', 'type', 'icon', 'color',
        'system_key', 'default_allocation', 'sort_order', 'archived_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('fin_transactions', [
        'id', 'household_id', 'type', 'account_id', 'to_account_id', 'category_id',
        'amount', 'occurred_on', 'note', 'payee', 'planned_item_id', 'related_transaction_id',
        'source_type', 'source_id', 'allocation', 'allocation_detail', 'allocation_reversal_of_id',
    ]))->toBeTrue();

    expect(Schema::hasColumns('fin_budgets', [
        'id', 'household_id', 'category_id', 'period', 'amount', 'alert_80_sent_at', 'alert_100_sent_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('fin_allocation_plans', [
        'id', 'household_id', 'period', 'income_base', 'living_bps', 'tertiary_bps',
        'debt_bps', 'savings_bps', 'social_bps',
    ]))->toBeTrue();

    expect(Schema::hasColumns('fin_recurring_bills', [
        'id', 'household_id', 'name', 'direction', 'category_id', 'account_id',
        'amount', 'is_variable_amount', 'rrule', 'dtstart', 'until', 'remind_days_before',
        'is_active', 'generated_until',
    ]))->toBeTrue();

    expect(Schema::hasColumns('fin_loans', [
        'id', 'household_id', 'direction', 'name', 'counterparty', 'principal',
        'installment_count', 'installment_amount', 'total_payable', 'frequency',
        'first_due_date', 'account_id', 'category_id', 'disbursement_transaction_id',
        'interest_note', 'remind_days_before', 'status', 'paid_off_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('fin_planned_items', [
        'id', 'household_id', 'direction', 'name', 'category_id', 'account_id',
        'amount', 'paid_amount', 'due_date', 'period', 'status', 'status_reason',
        'source_type', 'source_id', 'sequence', 'paid_before_tracking', 'settled_at',
    ]))->toBeTrue();
});

it('verifies all finance enums have valid labels and helper values', function (): void {
    // AccountType
    expect(AccountType::Cash->label())->toBe('Tunai')
        ->and(AccountType::Cash->isOperational())->toBeTrue()
        ->and(AccountType::Savings->isSavings())->toBeTrue()
        ->and(AccountType::CreditCard->icon())->toBe('credit-card');

    // CategoryType
    expect(CategoryType::Income->label())->toBe('Pemasukan')
        ->and(CategoryType::Expense->label())->toBe('Pengeluaran');

    // TransactionType
    expect(TransactionType::Income->label())->toBe('Pemasukan')
        ->and(TransactionType::Expense->label())->toBe('Pengeluaran')
        ->and(TransactionType::Transfer->label())->toBe('Transfer');

    // Direction
    expect(Direction::Income->label())->toBe('Pemasukan')
        ->and(Direction::Expense->label())->toBe('Pengeluaran');

    // LoanDirection
    expect(LoanDirection::Payable->isPayable())->toBeTrue()
        ->and(LoanDirection::Receivable->isReceivable())->toBeTrue();

    // LoanFrequency
    expect(LoanFrequency::Monthly->label())->toBe('Bulanan')
        ->and(LoanFrequency::Weekly->label())->toBe('Mingguan')
        ->and(LoanFrequency::Once->label())->toBe('Sekali');

    // LoanStatus
    expect(LoanStatus::Active->badgeColor())->toBe('warning')
        ->and(LoanStatus::PaidOff->badgeColor())->toBe('success')
        ->and(LoanStatus::Cancelled->badgeColor())->toBe('neutral');

    // PlannedStatus
    expect(PlannedStatus::Planned->badgeColor())->toBe('neutral')
        ->and(PlannedStatus::Partial->badgeColor())->toBe('warning')
        ->and(PlannedStatus::Paid->badgeColor())->toBe('success');

    // PlannedSource
    expect(PlannedSource::Manual->icon())->toBe('document-text')
        ->and(PlannedSource::Loan->icon())->toBe('banknotes');

    // AllocationCategory
    expect(AllocationCategory::Living->label())->toBe('Kebutuhan Hidup')
        ->and(AllocationCategory::Tertiary->label())->toBe('Tersier')
        ->and(AllocationCategory::Debt->label())->toBe('Cicilan / Utang')
        ->and(AllocationCategory::Savings->label())->toBe('Tabungan & Investasi')
        ->and(AllocationCategory::Social->label())->toBe('Donasi / Sosial / Zakat');
});

it('enforces unique constraints and foreign keys across finance tables', function (): void {
    $context = app(HouseholdContext::class);

    $context->runInContext($this->household, function (): void {
        // Budget unique constraint per household, category, and period
        $cat = Category::create([
            'household_id' => $this->household->getKey(),
            'name' => 'Makanan Pokok',
            'type' => CategoryType::Expense,
        ]);

        Budget::create([
            'household_id' => $this->household->getKey(),
            'category_id' => $cat->getKey(),
            'period' => '2026-11-01',
            'amount' => 2000000,
        ]);

        expect(function () use ($cat): void {
            Budget::create([
                'household_id' => $this->household->getKey(),
                'category_id' => $cat->getKey(),
                'period' => '2026-11-01',
                'amount' => 2500000,
            ]);
        })->toThrow(QueryException::class);

        // AllocationPlan unique constraint per household and period
        AllocationPlan::create([
            'household_id' => $this->household->getKey(),
            'period' => '2026-11-01',
            'income_base' => 10000000,
            'living_bps' => 5000,
        ]);

        expect(function (): void {
            AllocationPlan::create([
                'household_id' => $this->household->getKey(),
                'period' => '2026-11-01',
                'income_base' => 12000000,
            ]);
        })->toThrow(QueryException::class);
    });
});

it('calculates allocation plan targets accurately from integer basis points', function (): void {
    $plan = new AllocationPlan([
        'income_base' => 15000000,
        'living_bps' => 5000,    // 50%
        'tertiary_bps' => 1500,  // 15%
        'debt_bps' => 2000,      // 20%
        'savings_bps' => 1000,   // 10%
        'social_bps' => 500,     // 5%
    ]);

    expect($plan->livingTarget())->toBe(7500000)
        ->and($plan->tertiaryTarget())->toBe(2250000)
        ->and($plan->debtTarget())->toBe(3000000)
        ->and($plan->savingsTarget())->toBe(1500000)
        ->and($plan->socialTarget())->toBe(750000)
        ->and($plan->totalTarget())->toBe(15000000)
        ->and($plan->unallocatedTarget())->toBe(0)
        ->and($plan->totalBps())->toBe(10000)
        ->and($plan->unallocatedBps())->toBe(0)
        ->and($plan->debtRatioPercent())->toBe(20.0);
});

it('calculates unallocated target when sum of bps is less than 10000', function (): void {
    $plan = new AllocationPlan([
        'income_base' => 10000000,
        'living_bps' => 5000,    // 50% = 5.000.000
        'tertiary_bps' => 1000,  // 10% = 1.000.000
        'debt_bps' => 1000,      // 10% = 1.000.000
        'savings_bps' => 1000,   // 10% = 1.000.000
        'social_bps' => 0,       // 0%
    ]);

    expect($plan->totalTarget())->toBe(8000000)
        ->and($plan->unallocatedTarget())->toBe(2000000)
        ->and($plan->unallocatedBps())->toBe(2000);
});
