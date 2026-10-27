<?php

declare(strict_types=1);

namespace App\Modules\Finance\Livewire\Plan;

use App\Core\Support\HouseholdContext;
use App\Core\Support\Money;
use App\Core\Support\Period;
use App\Core\Support\PeriodResolver;
use App\Modules\Finance\Actions\RecomputePlannedItem;
use App\Modules\Finance\Enums\Direction;
use App\Modules\Finance\Enums\PlannedSource;
use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Events\TransactionRecorded;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\AllocationPlan;
use App\Modules\Finance\Models\Budget;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\PlannedItem;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Services\AccountBalanceService;
use App\Modules\Finance\Services\AllocationService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

final class Index extends Component
{
    public ?string $periodKey = null;

    // Modals
    public bool $showPayModal = false;
    public bool $showSkipModal = false;
    public bool $showManualModal = false;
    public bool $showAllocationModal = false;

    // Pay Modal Fields
    public ?string $payItemId = null;
    public string $payItemName = '';
    public string $payAmount = '';
    public ?string $payAccountId = null;
    public string $payDate = '';
    public string $payNote = '';

    // Skip Modal Fields
    public ?string $skipItemId = null;
    public string $skipReason = '';

    // Manual Planned Item Fields
    public string $manualName = '';
    public string $manualDirection = 'expense';
    public ?string $manualCategoryId = null;
    public ?string $manualAccountId = null;
    public string $manualAmount = '';
    public string $manualDueDate = '';

    // Allocation Plan Form Fields
    public string $allocIncomeBase = '0';
    public string $allocLivingPct = '50';
    public string $allocTertiaryPct = '20';
    public string $allocDebtPct = '10';
    public string $allocSavingsPct = '10';
    public string $allocSocialPct = '10';

    /** @var array<string, mixed> */
    protected $queryString = [
        'periodKey' => ['except' => null, 'as' => 'period'],
    ];

    public function mount(HouseholdContext $context): void
    {
        $household = $context->current();
        $resolver = PeriodResolver::for($household);

        if ($this->periodKey === null) {
            $this->periodKey = $resolver->current()->key;
        }

        $this->payDate = now()->toDateString();
        $this->manualDueDate = now()->toDateString();
    }

    public function openPay(string $itemId): void
    {
        $item = PlannedItem::query()->findOrFail($itemId);
        $this->payItemId = $item->id;
        $this->payItemName = $item->name;
        $this->payAmount = (string) $item->remainingAmount();
        $this->payAccountId = $item->account_id ?? Account::query()->where('household_id', $item->household_id)->first()?->id;
        $this->payDate = now()->toDateString();
        $this->payNote = '';
        $this->showPayModal = true;
    }

    public function executePayment(RecomputePlannedItem $recomputeAction, AccountBalanceService $balanceService): void
    {
        $this->validate([
            'payAmount' => ['required'],
            'payAccountId' => ['required', 'exists:fin_accounts,id'],
            'payDate' => ['required', 'date'],
        ]);

        $item = PlannedItem::query()->findOrFail($this->payItemId);
        $parsedAmount = Money::parse($this->payAmount);
        $date = CarbonImmutable::parse($this->payDate);

        DB::transaction(function () use ($item, $parsedAmount, $date, $recomputeAction, $balanceService): void {
            $type = $item->direction === Direction::Expense ? TransactionType::Expense : TransactionType::Income;

            $tx = new Transaction();
            $tx->household_id = $item->household_id;
            $tx->type = $type;
            $tx->account_id = $this->payAccountId;
            $tx->category_id = $item->category_id;
            $tx->planned_item_id = $item->id;
            $tx->amount = $parsedAmount;
            $tx->occurred_on = $date;
            $tx->note = $this->payNote !== '' ? $this->payNote : "Pembayaran {$item->name}";
            $tx->allocation = $item->category->default_allocation;
            $tx->created_by = auth()->id();
            $tx->save();

            $balanceService->bustCache($this->payAccountId);
            TransactionRecorded::dispatch($tx->id);

            $recomputeAction->execute($item);
        });

        $this->showPayModal = false;
        session()->flash('message', "Pembayaran untuk '{$item->name}' berhasil dicatat.");
    }

    public function openSkip(string $itemId): void
    {
        $this->skipItemId = $itemId;
        $this->skipReason = '';
        $this->showSkipModal = true;
    }

    public function executeSkip(): void
    {
        $this->validate([
            'skipReason' => ['required', 'string', 'min:3', 'max:150'],
        ]);

        $item = PlannedItem::query()->findOrFail($this->skipItemId);
        $item->status = PlannedStatus::Skipped;
        $item->status_reason = $this->skipReason;
        $item->save();

        $this->showSkipModal = false;
        session()->flash('message', "Kewajiban '{$item->name}' ditandai dilewati.");
    }

    public function unskipItem(string $itemId, RecomputePlannedItem $recomputeAction): void
    {
        $item = PlannedItem::query()->findOrFail($itemId);
        $item->status = PlannedStatus::Planned;
        $item->status_reason = null;
        $item->save();

        $recomputeAction->execute($item);
        session()->flash('message', "Status kewajiban '{$item->name}' dikembalikan.");
    }

    public function openManualModal(): void
    {
        $this->manualName = '';
        $this->manualDirection = 'expense';
        $this->manualCategoryId = null;
        $this->manualAccountId = null;
        $this->manualAmount = '';
        $this->manualDueDate = now()->toDateString();
        $this->showManualModal = true;
    }

    public function saveManualItem(HouseholdContext $context): void
    {
        $this->validate([
            'manualName' => ['required', 'string', 'max:100'],
            'manualCategoryId' => ['required', 'exists:fin_categories,id'],
            'manualAmount' => ['required'],
            'manualDueDate' => ['required', 'date'],
        ]);

        $household = $context->current();
        $resolver = PeriodResolver::for($household);
        $dueDate = CarbonImmutable::parse($this->manualDueDate);
        $period = $resolver->forDate($dueDate)->key;

        $item = new PlannedItem();
        $item->household_id = $household->id;
        $item->name = $this->manualName;
        $item->direction = Direction::from($this->manualDirection);
        $item->category_id = $this->manualCategoryId;
        $item->account_id = $this->manualAccountId;
        $item->amount = Money::parse($this->manualAmount);
        $item->due_date = $dueDate;
        $item->period = $period;
        $item->status = PlannedStatus::Planned;
        $item->source_type = PlannedSource::Manual;
        $item->created_by = auth()->id();
        $item->save();

        $this->showManualModal = false;
        session()->flash('message', "Rencana '{$item->name}' berhasil ditambahkan.");
    }

    public function openAllocationModal(HouseholdContext $context): void
    {
        $household = $context->current();
        $plan = AllocationPlan::query()
            ->where('household_id', $household->id)
            ->where('period', $this->periodKey)
            ->first();

        if ($plan !== null) {
            $this->allocIncomeBase = (string) $plan->income_base;
            $this->allocLivingPct = (string) ($plan->living_bps / 100);
            $this->allocTertiaryPct = (string) ($plan->tertiary_bps / 100);
            $this->allocDebtPct = (string) ($plan->debt_bps / 100);
            $this->allocSavingsPct = (string) ($plan->savings_bps / 100);
            $this->allocSocialPct = (string) ($plan->social_bps / 100);
        } else {
            $this->allocIncomeBase = '0';
            $this->allocLivingPct = '50';
            $this->allocTertiaryPct = '20';
            $this->allocDebtPct = '10';
            $this->allocSavingsPct = '10';
            $this->allocSocialPct = '10';
        }

        $this->showAllocationModal = true;
    }

    public function saveAllocationPlan(HouseholdContext $context): void
    {
        $incomeBase = Money::parse($this->allocIncomeBase);
        $livingBps = (int) round(((float) $this->allocLivingPct) * 100);
        $tertiaryBps = (int) round(((float) $this->allocTertiaryPct) * 100);
        $debtBps = (int) round(((float) $this->allocDebtPct) * 100);
        $savingsBps = (int) round(((float) $this->allocSavingsPct) * 100);
        $socialBps = (int) round(((float) $this->allocSocialPct) * 100);

        $totalBps = $livingBps + $tertiaryBps + $debtBps + $savingsBps + $socialBps;
        if ($totalBps > 10000) {
            $this->addError('allocLivingPct', 'Total persentase alokasi tidak boleh melebihi 100%.');

            return;
        }

        $household = $context->current();
        $plan = AllocationPlan::query()
            ->where('household_id', $household->id)
            ->where('period', $this->periodKey)
            ->first();

        if ($plan === null) {
            $plan = new AllocationPlan();
            $plan->household_id = $household->id;
            $plan->period = $this->periodKey;
            $plan->created_by = auth()->id();
        }

        $plan->income_base = $incomeBase;
        $plan->living_bps = $livingBps;
        $plan->tertiary_bps = $tertiaryBps;
        $plan->debt_bps = $debtBps;
        $plan->savings_bps = $savingsBps;
        $plan->social_bps = $socialBps;
        $plan->save();

        $this->showAllocationModal = false;
        session()->flash('message', 'Konfigurasi alokasi dana berhasil disimpan.');
    }

    public function render(HouseholdContext $context, AllocationService $allocationService): View
    {
        $household = $context->current();
        $resolver = PeriodResolver::for($household);

        /** @var Period $period */
        $period = $resolver->fromKey($this->periodKey ?? $resolver->current()->key);
        $prevPeriod = $resolver->previous($period);
        $nextPeriod = $resolver->next($period);

        // Fetch all PlannedItems in this period
        $allItems = PlannedItem::query()
            ->where('household_id', $household->id)
            ->where('period', $period->key)
            ->with(['category.parent', 'account'])
            ->orderBy('due_date')
            ->get();

        // Income planned items
        $incomeItems = $allItems->where('direction', Direction::Income);
        $planIncomeTotal = $incomeItems->sum('amount');

        // Expense planned items (Fixed obligations)
        $expenseItems = $allItems->where('direction', Direction::Expense);
        $obligationsTotal = $expenseItems->sum('amount');

        // Group expense items (US-FIN-07 AC3): Overdue -> Unpaid -> Partial -> Paid -> Skipped
        $overdueItems = $expenseItems->filter(fn (PlannedItem $i) => $i->isOverdue());
        $unpaidItems = $expenseItems->filter(fn (PlannedItem $i) => $i->status === PlannedStatus::Planned && ! $i->isOverdue());
        $partialItems = $expenseItems->filter(fn (PlannedItem $i) => $i->status === PlannedStatus::Partial && ! $i->isOverdue());
        $paidItems = $expenseItems->filter(fn (PlannedItem $i) => $i->status === PlannedStatus::Paid || $i->paid_before_tracking);
        $skippedItems = $expenseItems->filter(fn (PlannedItem $i) => $i->status === PlannedStatus::Skipped || $i->status === PlannedStatus::Cancelled);

        $unpaidObligationsCount = $overdueItems->count() + $unpaidItems->count() + $partialItems->count();
        $unpaidObligationsAmount = $overdueItems->sum(fn ($i) => $i->remainingAmount())
            + $unpaidItems->sum(fn ($i) => $i->remainingAmount())
            + $partialItems->sum(fn ($i) => $i->remainingAmount());

        // Variable budgets in period
        $variableBudgetTotal = (int) Budget::query()
            ->where('household_id', $household->id)
            ->where('period', $period->key)
            ->sum('amount');

        // Summary Calculations (US-FIN-07 AC2):
        // Rencana Keluar = Kewajiban Tetap + Anggaran Variabel
        $planExpenseTotal = $obligationsTotal + $variableBudgetTotal;
        $remainingPlan = $planIncomeTotal - $planExpenseTotal;

        // Actual Realizations
        $startDate = $period->start->toDateString();
        $endDate = $period->end->toDateString();

        $realizedIncome = (int) Transaction::query()
            ->where('household_id', $household->id)
            ->where('type', TransactionType::Income)
            ->whereBetween('occurred_on', [$startDate, $endDate])
            ->sum('amount');

        $realizedExpense = (int) Transaction::query()
            ->where('household_id', $household->id)
            ->where('type', TransactionType::Expense)
            ->whereBetween('occurred_on', [$startDate, $endDate])
            ->sum('amount');

        // Allocation Summary (US-FIN-04A)
        $allocationSummary = $allocationService->getAllocationSummary($household, $period->key);

        $accounts = Account::query()
            ->where('household_id', $household->id)
            ->whereNull('archived_at')
            ->orderBy('sort_order')
            ->get();

        $categories = Category::query()
            ->where('household_id', $household->id)
            ->whereNull('archived_at')
            ->with('parent')
            ->orderBy('sort_order')
            ->get();

        return view('finance::plan.index', [
            'period' => $period,
            'prevPeriod' => $prevPeriod,
            'nextPeriod' => $nextPeriod,
            'planIncomeTotal' => $planIncomeTotal,
            'obligationsTotal' => $obligationsTotal,
            'variableBudgetTotal' => $variableBudgetTotal,
            'planExpenseTotal' => $planExpenseTotal,
            'remainingPlan' => $remainingPlan,
            'realizedIncome' => $realizedIncome,
            'realizedExpense' => $realizedExpense,
            'unpaidObligationsCount' => $unpaidObligationsCount,
            'unpaidObligationsAmount' => $unpaidObligationsAmount,
            'overdueItems' => $overdueItems,
            'unpaidItems' => $unpaidItems,
            'partialItems' => $partialItems,
            'paidItems' => $paidItems,
            'skippedItems' => $skippedItems,
            'incomeItems' => $incomeItems,
            'expenseItems' => $expenseItems,
            'allocationSummary' => $allocationSummary,
            'accounts' => $accounts,
            'categories' => $categories,
        ])->layout('layouts.app', ['title' => 'Rencana Bulanan']);
    }
}
