<?php

declare(strict_types=1);

namespace App\Modules\Finance\Livewire\Budgets;

use App\Core\Support\HouseholdContext;
use App\Core\Support\Money;
use App\Core\Support\Period;
use App\Core\Support\PeriodResolver;
use App\Modules\Finance\Enums\AllocationCategory;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Models\AllocationPlan;
use App\Modules\Finance\Models\Budget;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Services\BudgetService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

final class Index extends Component
{
    public ?string $periodKey = null;

    // Income basis for percentage calculation
    public string $incomeBaseInput = '0';
    public bool $isCustomIncome = false;

    /** @var array<string, int> */
    public array $percentages = [];

    // Edit modal
    public bool $showConfigureModal = false;

    /** @var array<string, mixed> */
    protected $queryString = [
        'periodKey' => ['except' => null, 'as' => 'period'],
    ];

    public function mount(HouseholdContext $context, BudgetService $budgetService): void
    {
        $household = $context->current();
        $resolver = PeriodResolver::for($household);

        if ($this->periodKey === null) {
            $this->periodKey = $resolver->current()->key;
        }

        $this->loadData($context, $budgetService);
    }

    public function loadData(HouseholdContext $context, BudgetService $budgetService): void
    {
        $household = $context->current();
        $resolver = PeriodResolver::for($household);
        /** @var Period $period */
        $period = $resolver->fromKey($this->periodKey ?? $resolver->current()->key);

        $totalIncome = $budgetService->getTotalIncome($household, $period);
        $this->incomeBaseInput = (string) $totalIncome;

        $categories = Category::query()
            ->where('household_id', $household->id)
            ->whereNull('parent_id')
            ->where('type', CategoryType::Expense)
            ->whereNotIn('system_key', ['balance_adjustment', 'loan_disbursement_out'])
            ->whereNull('archived_at')
            ->orderBy('sort_order')
            ->get();

        $existingBudgets = Budget::query()
            ->where('household_id', $household->id)
            ->where('period', $period->key)
            ->get()
            ->keyBy('category_id');

        $this->percentages = [];

        // Standard default breakdown for 5 categories: 50% Living, 20% Tertiary, 10% Savings, 10% Social, 10% Debt
        $defaultPctMap = [
            AllocationCategory::Living->value => 50,
            AllocationCategory::Tertiary->value => 20,
            AllocationCategory::Savings->value => 10,
            AllocationCategory::Social->value => 10,
            AllocationCategory::Debt->value => 10,
        ];

        foreach ($categories as $cat) {
            $budget = $existingBudgets->get($cat->id);
            if ($budget !== null && $budget->percentage > 0) {
                $this->percentages[$cat->id] = (int) $budget->percentage;
            } else {
                $allocKey = $cat->default_allocation !== null ? $cat->default_allocation->value : AllocationCategory::Living->value;
                $this->percentages[$cat->id] = $defaultPctMap[$allocKey];
            }
        }
    }

    public function openConfigure(): void
    {
        $this->resetErrorBag();
        $this->showConfigureModal = true;
    }

    public function closeConfigure(): void
    {
        $this->showConfigureModal = false;
    }

    public function getTotalPercentageProperty(): int
    {
        return (int) array_sum($this->percentages);
    }

    public function savePercentages(HouseholdContext $context, BudgetService $budgetService): void
    {
        $this->resetErrorBag();

        $totalPct = array_sum($this->percentages);
        if ($totalPct !== 100) {
            $this->addError('percentages', "Total persentase harus tepat 100%. Saat ini: {$totalPct}%.");

            return;
        }

        $parsedIncome = 0;
        try {
            $parsedIncome = Money::parse($this->incomeBaseInput);
        } catch (\Throwable) {
            $this->addError('incomeBaseInput', 'Nominal estimasi pemasukan tidak valid.');

            return;
        }

        $household = $context->current();
        $resolver = PeriodResolver::for($household);
        /** @var Period $period */
        $period = $resolver->fromKey($this->periodKey ?? $resolver->current()->key);

        DB::transaction(function () use ($household, $period, $parsedIncome, $budgetService): void {
            // 1. Save or update Budget for each category
            $allocBps = [
                'living' => 0,
                'tertiary' => 0,
                'debt' => 0,
                'savings' => 0,
                'social' => 0,
            ];

            foreach ($this->percentages as $catId => $pct) {
                $nominal = (int) floor(($parsedIncome * $pct) / 100);

                $budget = Budget::query()
                    ->where('household_id', $household->id)
                    ->where('category_id', $catId)
                    ->where('period', $period->key)
                    ->first();

                if ($budget === null) {
                    $budget = new Budget();
                    $budget->household_id = $household->id;
                    $budget->category_id = $catId;
                    $budget->period = $period->key;
                    $budget->created_by = auth()->id();
                }

                $budget->percentage = (int) $pct;
                $budget->amount = $nominal;
                $budget->save();

                $category = Category::find($catId);
                if ($category !== null && $category->default_allocation !== null) {
                    $key = $category->default_allocation->value;
                    $allocBps[$key] = (int) ($pct * 100);
                }

                $budget->load(['category', 'household']);
                $budgetService->checkThresholds($budget);
            }

            // 2. Sync to AllocationPlan
            $plan = AllocationPlan::query()
                ->where('household_id', $household->id)
                ->where('period', $period->key)
                ->first();

            if ($plan === null) {
                $plan = new AllocationPlan();
                $plan->household_id = $household->id;
                $plan->period = $period->key;
                $plan->created_by = auth()->id();
            }

            $plan->income_base = $parsedIncome;
            $plan->living_bps = $allocBps['living'];
            $plan->tertiary_bps = $allocBps['tertiary'];
            $plan->debt_bps = $allocBps['debt'];
            $plan->savings_bps = $allocBps['savings'];
            $plan->social_bps = $allocBps['social'];
            $plan->save();
        });

        $this->showConfigureModal = false;
        session()->flash('message', 'Anggaran persentase (total 100%) berhasil disimpan.');
    }

    public function copyPrevious(HouseholdContext $context, BudgetService $budgetService): void
    {
        $household = $context->current();
        $resolver = PeriodResolver::for($household);

        /** @var Period $period */
        $period = $resolver->fromKey($this->periodKey);
        $prevPeriod = $resolver->previous($period);

        $copied = $budgetService->copyFromPreviousPeriod($household, $period->key, $prevPeriod->key);

        $this->loadData($context, $budgetService);
        session()->flash('message', "{$copied} anggaran berhasil disalin dari periode sebelumnya.");
    }

    public function render(HouseholdContext $context, BudgetService $budgetService): View
    {
        $household = $context->current();
        $resolver = PeriodResolver::for($household);

        /** @var Period $period */
        $period = $resolver->fromKey($this->periodKey ?? $resolver->current()->key);
        $prevPeriod = $resolver->previous($period);
        $nextPeriod = $resolver->next($period);

        $totalIncome = $budgetService->getTotalIncome($household, $period);
        $budgetSummaries = $budgetService->getBudgetSummary($household, $period->key);

        $totalBudget = $budgetSummaries->sum('budget_amount');
        $totalSpent = $budgetSummaries->sum('spent_amount');
        $totalRemaining = $totalBudget - $totalSpent;

        return view('finance::budgets.index', [
            'period' => $period,
            'prevPeriod' => $prevPeriod,
            'nextPeriod' => $nextPeriod,
            'totalIncome' => $totalIncome,
            'budgetSummaries' => $budgetSummaries,
            'totalBudget' => $totalBudget,
            'totalSpent' => $totalSpent,
            'totalRemaining' => $totalRemaining,
        ])->layout('layouts.app', ['title' => 'Anggaran Kategori']);
    }
}
