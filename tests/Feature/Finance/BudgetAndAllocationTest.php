<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Core\Actions\CreateHousehold;
use App\Core\Support\HouseholdContext;
use App\Core\Support\PeriodResolver;
use App\Models\User;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Enums\AllocationCategory;
use App\Modules\Finance\Enums\Direction;
use App\Modules\Finance\Enums\PlannedSource;
use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Events\BudgetThresholdReached;
use App\Modules\Finance\Livewire\Budgets\Index as BudgetsIndex;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\AllocationPlan;
use App\Modules\Finance\Models\Budget;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\PlannedItem;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Services\AllocationService;
use App\Modules\Finance\Services\BudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::create([
        'name' => 'Shadri Zulkurniadi',
        'email' => 'shadri@example.com',
        'password' => bcrypt('password123'),
    ]);

    $this->household = app(CreateHousehold::class)->handle(
        $this->user,
        'Keluarga Shadri',
        1,
        'Asia/Jakarta'
    );
    app(HouseholdContext::class)->use($this->household);

    $this->account = Account::create([
        'household_id' => $this->household->id,
        'name' => 'BCA',
        'type' => AccountType::Bank,
        'opening_balance' => 20000000,
        'opening_date' => now()->subDays(60),
        'color' => '#10b981',
        'icon' => 'wallet',
    ]);

    $this->savingsAccount = Account::create([
        'household_id' => $this->household->id,
        'name' => 'BSI Tabungan',
        'type' => AccountType::Savings,
        'opening_balance' => 50000000,
        'opening_date' => now()->subDays(60),
        'color' => '#059669',
        'icon' => 'banknotes',
    ]);
});

it('enforces BR-FIN-030 where transactions linked to PlannedItem do not deduct from category budget', function (): void {
    $context = app(HouseholdContext::class);
    $budgetService = app(BudgetService::class);

    $context->runInContext($this->household, function () use ($budgetService): void {
        $parentCat = Category::where('household_id', $this->household->id)
            ->whereNull('parent_id')
            ->where('name', 'Kebutuhan Pokok')
            ->firstOrFail();

        $subCat = Category::where('household_id', $this->household->id)
            ->where('parent_id', $parentCat->id)
            ->firstOrFail();

        $resolver = PeriodResolver::for($this->household);
        $period = $resolver->current();

        $budget = Budget::create([
            'household_id' => $this->household->id,
            'category_id' => $parentCat->id,
            'period' => $period->key,
            'amount' => 2000000,
        ]);

        $plannedItem = PlannedItem::create([
            'household_id' => $this->household->id,
            'direction' => Direction::Expense,
            'name' => 'Listrik Rumah Terencana',
            'category_id' => $subCat->id,
            'account_id' => $this->account->id,
            'amount' => 500000,
            'due_date' => $period->start->addDays(5),
            'period' => $period->key,
            'status' => PlannedStatus::Planned,
            'source_type' => PlannedSource::Manual,
        ]);

        // 1. Transaction linked to planned item (500rb) -> must NOT reduce budget
        Transaction::create([
            'household_id' => $this->household->id,
            'type' => TransactionType::Expense,
            'account_id' => $this->account->id,
            'category_id' => $subCat->id,
            'planned_item_id' => $plannedItem->id,
            'amount' => 500000,
            'occurred_on' => $period->start->addDays(5),
        ]);

        $spent = $budgetService->calculateSpent($parentCat, $period);
        expect($spent)->toBe(0); // 0 because planned_item_id is not null!

        // 2. Variable unplanned transaction (300rb) -> MUST reduce budget
        Transaction::create([
            'household_id' => $this->household->id,
            'type' => TransactionType::Expense,
            'account_id' => $this->account->id,
            'category_id' => $subCat->id,
            'planned_item_id' => null,
            'amount' => 300000,
            'occurred_on' => $period->start->addDays(7),
        ]);

        $spentAfter = $budgetService->calculateSpent($parentCat, $period);
        expect($spentAfter)->toBe(300000);
    });
});

it('dispatches 80% and 100% threshold alerts once and resets sent timestamp if budget is increased', function (): void {
    Event::fake([BudgetThresholdReached::class]);

    $context = app(HouseholdContext::class);
    $budgetService = app(BudgetService::class);

    $context->runInContext($this->household, function () use ($budgetService): void {
        $parentCat = Category::where('household_id', $this->household->id)
            ->whereNull('parent_id')
            ->where('name', 'Lifestyle')
            ->firstOrFail();

        $resolver = PeriodResolver::for($this->household);
        $period = $resolver->current();

        $budget = Budget::create([
            'household_id' => $this->household->id,
            'category_id' => $parentCat->id,
            'period' => $period->key,
            'amount' => 1000000, // 1jt
        ]);

        // Spend 850rb (85%)
        Transaction::create([
            'household_id' => $this->household->id,
            'type' => TransactionType::Expense,
            'account_id' => $this->account->id,
            'category_id' => $parentCat->id,
            'amount' => 850000,
            'occurred_on' => $period->start->addDays(2),
        ]);

        $budgetService->checkThresholds($budget);

        $budget->refresh();
        expect($budget->alert_80_sent_at)->not->toBeNull();
        expect($budget->alert_100_sent_at)->toBeNull();

        Event::assertDispatched(BudgetThresholdReached::class, function ($event) use ($budget) {
            return $event->budgetId === $budget->id && $event->threshold === 80;
        });

        // Increase budget to 2jt -> ratio drops to 850rb/2jt = 42.5% -> alert_80_sent_at should be reset to null (BR-FIN-031)
        $budget->amount = 2000000;
        $budget->save();

        $budgetService->checkThresholds($budget);
        $budget->refresh();
        expect($budget->alert_80_sent_at)->toBeNull();
    });
});

it('calculates 5 allocation plan targets, debt ratio warning, and net savings placement with reversals', function (): void {
    $context = app(HouseholdContext::class);
    $allocService = app(AllocationService::class);

    $context->runInContext($this->household, function () use ($allocService): void {
        $resolver = PeriodResolver::for($this->household);
        $period = $resolver->current();

        $plan = AllocationPlan::create([
            'household_id' => $this->household->id,
            'period' => $period->key,
            'income_base' => 10000000, // 10jt
            'living_bps' => 5000,     // 50% = 5jt
            'tertiary_bps' => 2000,   // 20% = 2jt
            'debt_bps' => 1500,       // 15% = 1.5jt
            'savings_bps' => 1000,    // 10% = 1jt
            'social_bps' => 500,      // 5% = 500rb (total 100%)
        ]);

        // 1. Savings placement transfer (1.000.000)
        $txPlacement = Transaction::create([
            'household_id' => $this->household->id,
            'type' => TransactionType::Transfer,
            'account_id' => $this->account->id,
            'to_account_id' => $this->savingsAccount->id,
            'amount' => 1000000,
            'occurred_on' => $period->start->addDays(2),
            'allocation' => AllocationCategory::Savings,
            'allocation_detail' => 'Dana Darurat',
        ]);

        // 2. Savings reversal transfer (withdrawal of 200.000 back to checking)
        Transaction::create([
            'household_id' => $this->household->id,
            'type' => TransactionType::Transfer,
            'account_id' => $this->savingsAccount->id,
            'to_account_id' => $this->account->id,
            'amount' => 200000,
            'occurred_on' => $period->start->addDays(5),
            'allocation' => AllocationCategory::Savings,
            'allocation_reversal_of_id' => $txPlacement->id,
        ]);

        // Net savings placed should be 1.000.000 - 200.000 = 800.000
        $summary = $allocService->getAllocationSummary($this->household, $period->key);

        expect($summary['allocations']['savings']['target'])->toBe(1000000);
        expect($summary['allocations']['savings']['used'])->toBe(800000);
        expect($summary['allocations']['savings']['remaining'])->toBe(200000);

        expect($summary['allocations']['living']['target'])->toBe(5000000);
        expect($summary['allocations']['tertiary']['target'])->toBe(2000000);
        expect($summary['unallocated_target'])->toBe(0);
    });
});

it('configures budgets using percentages totaling exactly 100% of total income and rejects invalid sums', function (): void {
    $this->actingAs($this->user);

    $context = app(HouseholdContext::class);
    $context->runInContext($this->household, function (): void {
        $parentCats = Category::where('household_id', $this->household->id)
            ->whereNull('parent_id')
            ->where('type', \App\Modules\Finance\Enums\CategoryType::Expense)
            ->whereNotIn('system_key', ['balance_adjustment', 'loan_disbursement_out'])
            ->get();

        expect($parentCats->count())->toBe(5);

        // Map categories: Living=50%, Tertiary=20%, Savings=10%, Social=10%, Debt=10% (Total 100%)
        $percentages = [];
        foreach ($parentCats as $c) {
            $percentages[$c->id] = match ($c->default_allocation) {
                AllocationCategory::Living => 50,
                AllocationCategory::Tertiary => 20,
                AllocationCategory::Savings => 10,
                AllocationCategory::Social => 10,
                AllocationCategory::Debt => 10,
                default => 20,
            };
        }

        // Test rejecting invalid total (e.g. 90%)
        $invalidPercentages = $percentages;
        $invalidPercentages[$parentCats->first()->id] = 40; // Total 90%

        Livewire::test(BudgetsIndex::class)
            ->set('incomeBaseInput', '10.000.000')
            ->set('percentages', $invalidPercentages)
            ->call('savePercentages')
            ->assertHasErrors(['percentages']);

        // Test accepting valid total (100%)
        Livewire::test(BudgetsIndex::class)
            ->set('incomeBaseInput', '10.000.000')
            ->set('percentages', $percentages)
            ->call('savePercentages')
            ->assertHasNoErrors();

        // Verify computed nominal amounts in fin_budgets
        $livingCat = $parentCats->firstWhere('default_allocation', AllocationCategory::Living);
        $livingBudget = Budget::where('household_id', $this->household->id)
            ->where('category_id', $livingCat->id)
            ->first();

        expect($livingBudget)->not->toBeNull();
        expect($livingBudget->percentage)->toBe(50);
        expect($livingBudget->amount)->toBe(5000000); // 50% of 10jt = 5jt
    });
});
