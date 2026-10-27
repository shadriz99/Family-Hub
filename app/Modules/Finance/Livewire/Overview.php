<?php

declare(strict_types=1);

namespace App\Modules\Finance\Livewire;

use App\Core\Support\HouseholdContext;
use App\Core\Support\PeriodResolver;
use App\Modules\Finance\Enums\Direction;
use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\PlannedItem;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Services\AccountBalanceService;
use App\Modules\Finance\Services\AllocationService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class Overview extends Component
{
    public function render(
        HouseholdContext $context,
        AccountBalanceService $balanceService,
        AllocationService $allocationService
    ): View {
        $household = $context->current();
        $resolver = PeriodResolver::for($household);
        $currentPeriod = $resolver->current();

        $accounts = Account::query()
            ->where('household_id', $household->id)
            ->whereNull('archived_at')
            ->orderBy('sort_order')
            ->get()
            ->map(function (Account $account) use ($balanceService): array {
                return [
                    'account' => $account,
                    'balance' => $balanceService->getBalance($account),
                ];
            });

        $operationalBalance = $balanceService->getOperationalBalance($household);
        $savingsBalance = $balanceService->getSavingsBalance($household);
        $totalCombined = $balanceService->getTotalCombinedBalance($household);

        $latestTransactions = Transaction::query()
            ->where('household_id', $household->id)
            ->with(['account', 'category', 'creator'])
            ->orderByDesc('occurred_on')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $today = CarbonImmutable::now($household->timezone)->toDateString();
        $next7Days = CarbonImmutable::now($household->timezone)->addDays(7)->toDateString();

        $upcomingObligations = PlannedItem::query()
            ->where('household_id', $household->id)
            ->where('direction', Direction::Expense)
            ->whereIn('status', [PlannedStatus::Planned, PlannedStatus::Partial])
            ->whereBetween('due_date', [$today, $next7Days])
            ->orderBy('due_date')
            ->get();

        $allocationSummary = $allocationService->getAllocationSummary($household, $currentPeriod->key);

        return view('finance::overview', [
            'household' => $household,
            'currentPeriod' => $currentPeriod,
            'accounts' => $accounts,
            'operationalBalance' => $operationalBalance,
            'savingsBalance' => $savingsBalance,
            'totalCombined' => $totalCombined,
            'latestTransactions' => $latestTransactions,
            'upcomingObligations' => $upcomingObligations,
            'allocationSummary' => $allocationSummary,
        ])->layout('layouts.app', ['title' => 'Keuangan Keluarga']);
    }
}
