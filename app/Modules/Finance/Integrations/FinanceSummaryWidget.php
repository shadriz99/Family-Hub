<?php

declare(strict_types=1);

namespace App\Modules\Finance\Integrations;

use App\Core\Dashboard\DashboardWidget;
use App\Core\Models\Household;
use App\Core\Support\PeriodResolver;
use App\Modules\Finance\Enums\Direction;
use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Models\PlannedItem;
use App\Modules\Finance\Services\AccountBalanceService;
use App\Modules\Finance\Services\AllocationService;
use Illuminate\Contracts\View\View;

final class FinanceSummaryWidget implements DashboardWidget
{
    public function __construct(
        private readonly AccountBalanceService $balanceService,
        private readonly AllocationService $allocationService,
    ) {
    }

    public function key(): string
    {
        return 'finance.summary';
    }

    public function module(): string
    {
        return 'finance';
    }

    public function order(): int
    {
        return 10;
    }

    public function isEnabled(Household $household): bool
    {
        if (! config('modules.finance', false)) {
            return false;
        }

        try {
            return $household->financePeriodSettings()->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    public function render(Household $household): View
    {
        return app(\App\Core\Support\HouseholdContext::class)->runInContext($household, function () use ($household): View {
            $operationalBalance = $this->balanceService->getOperationalBalance($household);
            $savingsBalance = $this->balanceService->getSavingsBalance($household);
            $totalCombined = $this->balanceService->getTotalCombinedBalance($household);

            try {
                $resolver = PeriodResolver::for($household);
                $currentPeriod = $resolver->current();
            } catch (\InvalidArgumentException) {
                $currentPeriod = null;
            }

            $unpaidCount = 0;
            $unpaidTotal = 0;
            $allocationSummary = [];

            if ($currentPeriod !== null) {
                $unpaidItems = PlannedItem::query()
                    ->where('household_id', $household->id)
                    ->where('period', $currentPeriod->key)
                    ->where('direction', Direction::Expense)
                    ->whereIn('status', [PlannedStatus::Planned, PlannedStatus::Partial])
                    ->get();

                $unpaidCount = $unpaidItems->count();
                $unpaidTotal = $unpaidItems->sum(fn (PlannedItem $item) => $item->remainingAmount());

                $allocationSummary = $this->allocationService->getAllocationSummary($household, $currentPeriod->key);
            }

            return view('finance::widgets.summary', [
                'household' => $household,
                'operationalBalance' => $operationalBalance,
                'savingsBalance' => $savingsBalance,
                'totalCombined' => $totalCombined,
                'unpaidCount' => $unpaidCount,
                'unpaidTotal' => $unpaidTotal,
                'allocationSummary' => $allocationSummary,
                'currentPeriod' => $currentPeriod,
            ]);
        });
    }
}
