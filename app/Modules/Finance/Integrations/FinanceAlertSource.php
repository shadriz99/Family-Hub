<?php

declare(strict_types=1);

namespace App\Modules\Finance\Integrations;

use App\Core\Alerts\Alert;
use App\Core\Alerts\AlertSource;
use App\Core\Models\Household;
use App\Core\Support\Money;
use App\Core\Support\PeriodResolver;
use App\Modules\Finance\Enums\Direction;
use App\Modules\Finance\Enums\PlannedSource;
use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Models\PlannedItem;
use App\Modules\Finance\Services\BudgetService;
use Carbon\CarbonImmutable;

final class FinanceAlertSource implements AlertSource
{
    public function __construct(
        private readonly BudgetService $budgetService,
    ) {
    }

    /**
     * @return iterable<Alert>
     */
    public function alerts(Household $h): iterable
    {
        return app(\App\Core\Support\HouseholdContext::class)->runInContext($h, function () use ($h): iterable {
            try {
                $resolver = PeriodResolver::for($h);
            } catch (\Throwable) {
                return [];
            }

            try {
                $alerts = [];
                $today = CarbonImmutable::now($h->timezone)->toDateString();
                $currentPeriod = $resolver->current();
                $periodKey = $currentPeriod->key;

                // 1. Overdue expense PlannedItems (combined danger alert)
                $overdueExpenses = PlannedItem::query()
                    ->where('household_id', $h->id)
                    ->where('direction', Direction::Expense)
                    ->whereIn('status', [PlannedStatus::Planned, PlannedStatus::Partial])
                    ->where('due_date', '<', $today)
                    ->get();

                if ($overdueExpenses->isNotEmpty()) {
                    $count = $overdueExpenses->count();
                    $totalOverdue = $overdueExpenses->sum(fn (PlannedItem $item) => $item->remainingAmount());
                    $formattedTotal = Money::format($totalOverdue);

                    $alerts[] = new Alert(
                        level: 'danger',
                        title: "{$count} kewajiban terlambat dibayar ({$formattedTotal})",
                        url: "/finance/plan?period={$periodKey}",
                        sourceKey: 'finance.overdue_obligations',
                    );
                }

                // 2. Budget threshold alerts
                $budgetSummaries = $this->budgetService->getBudgetSummary($h, $periodKey);
                foreach ($budgetSummaries as $row) {
                    $budgetAmount = $row['budget_amount'];
                    if ($budgetAmount <= 0) {
                        continue;
                    }

                    $spent = $row['spent_amount'];
                    $pct = round(($spent / $budgetAmount) * 100);

                    if ($pct >= 100) {
                        $overPct = $pct - 100;
                        $alerts[] = new Alert(
                            level: 'danger',
                            title: "Anggaran {$row['category']->name} terlampaui {$overPct}%",
                            url: "/finance/budgets?period={$periodKey}",
                            sourceKey: "finance.budget_over_{$row['category']->id}",
                        );
                    } elseif ($pct >= 80) {
                        $alerts[] = new Alert(
                            level: 'warning',
                            title: "Anggaran {$row['category']->name} sudah {$pct}%",
                            url: "/finance/budgets?period={$periodKey}",
                            sourceKey: "finance.budget_warning_{$row['category']->id}",
                        );
                    }
                }

                // 3. Overdue receivable (piutang)
                $overdueReceivables = PlannedItem::query()
                    ->where('household_id', $h->id)
                    ->where('direction', Direction::Income)
                    ->where('source_type', PlannedSource::Loan)
                    ->whereIn('status', [PlannedStatus::Planned, PlannedStatus::Partial])
                    ->where('due_date', '<', $today)
                    ->get();

                foreach ($overdueReceivables as $item) {
                    $alerts[] = new Alert(
                        level: 'info',
                        title: "{$item->name} terlambat dibayar",
                        url: "/finance/plan?period={$periodKey}#item-{$item->id}",
                        sourceKey: "finance.receivable_overdue_{$item->id}",
                    );
                }

                return $alerts;
            } catch (\Illuminate\Database\QueryException) {
                return [];
            }
        });
    }
}
