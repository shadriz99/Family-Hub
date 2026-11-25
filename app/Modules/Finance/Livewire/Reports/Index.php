<?php

declare(strict_types=1);

namespace App\Modules\Finance\Livewire\Reports;

use App\Core\Support\HouseholdContext;
use App\Core\Support\Period;
use App\Core\Support\PeriodResolver;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Enums\LoanDirection;
use App\Modules\Finance\Enums\LoanStatus;
use App\Modules\Finance\Enums\PlannedSource;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Loan;
use App\Modules\Finance\Models\PlannedItem;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Models\TransactionSplit;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class Index extends Component
{
    public ?string $periodKey = null;

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
    }

    public function render(HouseholdContext $context): View
    {
        $household = $context->current();
        $resolver = PeriodResolver::for($household);

        /** @var Period $period */
        $period = $resolver->fromKey($this->periodKey ?? $resolver->current()->key);
        $prevPeriod = $resolver->previous($period);
        $nextPeriod = $resolver->next($period);

        $startDate = $period->start->toDateString();
        $endDate = $period->end->toDateString();

        // 1. Arus kas periode
        $totalIncome = (int) Transaction::query()
            ->where('household_id', $household->id)
            ->where('type', TransactionType::Income)
            ->whereBetween('occurred_on', [$startDate, $endDate])
            ->sum('amount');

        $totalExpense = (int) Transaction::query()
            ->where('household_id', $household->id)
            ->where('type', TransactionType::Expense)
            ->whereBetween('occurred_on', [$startDate, $endDate])
            ->sum('amount');

        $netCashFlow = $totalIncome - $totalExpense;

        // 2. Pengeluaran per kategori induk (US-FIN-09 AC2)
        $parentExpenseCategories = Category::query()
            ->where('household_id', $household->id)
            ->whereNull('parent_id')
            ->where('type', CategoryType::Expense)
            ->orderBy('sort_order')
            ->get();

        $categoryBreakdown = [];
        foreach ($parentExpenseCategories as $parent) {
            $childIds = Category::query()->where('parent_id', $parent->id)->pluck('id')->all();
            $allIds = array_merge([$parent->id], $childIds);

            $directSpent = (int) Transaction::query()
                ->where('household_id', $household->id)
                ->whereIn('category_id', $allIds)
                ->where('type', TransactionType::Expense)
                ->whereDoesntHave('splits')
                ->whereBetween('occurred_on', [$startDate, $endDate])
                ->sum('amount');

            $splitSpent = (int) TransactionSplit::query()
                ->where('household_id', $household->id)
                ->whereIn('category_id', $allIds)
                ->whereHas('transaction', function ($q) use ($startDate, $endDate): void {
                    $q->where('type', TransactionType::Expense)
                        ->whereBetween('occurred_on', [$startDate, $endDate]);
                })
                ->sum('amount');

            $spent = $directSpent + $splitSpent;

            if ($spent > 0) {
                $categoryBreakdown[] = [
                    'category' => $parent,
                    'amount' => $spent,
                    'percentage' => $totalExpense > 0 ? round(($spent / $totalExpense) * 100, 1) : 0,
                ];
            }
        }

        usort($categoryBreakdown, fn ($a, $b) => $b['amount'] <=> $a['amount']);

        // 3. Tren 12 periode (US-FIN-09 AC3)
        $twelvePeriods = [];
        $cursor = $period;
        for ($i = 0; $i < 12; $i++) {
            $pStart = $cursor->start->toDateString();
            $pEnd = $cursor->end->toDateString();

            $pInc = (int) Transaction::query()
                ->where('household_id', $household->id)
                ->where('type', TransactionType::Income)
                ->whereBetween('occurred_on', [$pStart, $pEnd])
                ->sum('amount');

            $pExp = (int) Transaction::query()
                ->where('household_id', $household->id)
                ->where('type', TransactionType::Expense)
                ->whereBetween('occurred_on', [$pStart, $pEnd])
                ->sum('amount');

            $twelvePeriods[] = [
                'period' => $cursor,
                'label' => $cursor->start->translatedFormat('M Y'),
                'income' => $pInc,
                'expense' => $pExp,
                'net' => $pInc - $pExp,
            ];

            $cursor = $resolver->previous($cursor);
        }
        $twelvePeriods = array_reverse($twelvePeriods);

        // 4. Ringkasan pinjaman (US-FIN-09 AC4)
        $activeLoans = Loan::query()
            ->where('household_id', $household->id)
            ->where('status', LoanStatus::Active)
            ->get();

        $totalPayableRemaining = 0;
        $totalReceivableRemaining = 0;

        foreach ($activeLoans as $l) {
            $items = PlannedItem::query()
                ->where('source_type', PlannedSource::Loan)
                ->where('source_id', $l->id)
                ->get();

            $paid = $items->sum('paid_amount') + $items->where('paid_before_tracking', true)->sum('amount');
            $rem = max(0, $l->total_payable - $paid);

            if ($l->direction === LoanDirection::Payable) {
                $totalPayableRemaining += $rem;
            } else {
                $totalReceivableRemaining += $rem;
            }
        }

        return view('finance::reports.index', [
            'period' => $period,
            'prevPeriod' => $prevPeriod,
            'nextPeriod' => $nextPeriod,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'netCashFlow' => $netCashFlow,
            'categoryBreakdown' => $categoryBreakdown,
            'twelvePeriods' => $twelvePeriods,
            'totalPayableRemaining' => $totalPayableRemaining,
            'totalReceivableRemaining' => $totalReceivableRemaining,
        ])->layout('layouts.app', ['title' => 'Laporan Keuangan']);
    }
}
