<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Models\Household;
use App\Core\Support\Period;
use App\Core\Support\PeriodResolver;
use App\Modules\Finance\Enums\AllocationCategory;
use App\Modules\Finance\Enums\Direction;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\AllocationPlan;
use App\Modules\Finance\Models\PlannedItem;
use App\Modules\Finance\Models\Transaction;

final class AllocationService
{
    /**
     * Calculates the full 5-allocation summary for a household and period.
     *
     * @return array{
     *     plan: AllocationPlan|null,
     *     income_base: int,
     *     unallocated_target: int,
     *     debt_ratio: float|null,
     *     debt_ratio_warning: bool,
     *     allocations: array<string, array{
     *         key: string,
     *         label: string,
     *         bps: int,
     *         percentage: float,
     *         target: int,
     *         used: int,
     *         remaining: int,
     *     }>
     * }
     */
    public function getAllocationSummary(Household $household, string $periodKey): array
    {
        $resolver = PeriodResolver::for($household);
        $period = $resolver->fromKey($periodKey);

        $plan = AllocationPlan::query()
            ->where('household_id', $household->id)
            ->where('period', $periodKey)
            ->first();

        $incomeBase = $plan !== null ? $plan->income_base : 0;

        $categories = [
            AllocationCategory::Living,
            AllocationCategory::Tertiary,
            AllocationCategory::Debt,
            AllocationCategory::Savings,
            AllocationCategory::Social,
        ];

        $allocationsData = [];
        $totalTarget = 0;

        foreach ($categories as $cat) {
            $bps = match ($cat) {
                AllocationCategory::Living => $plan !== null ? $plan->living_bps : 0,
                AllocationCategory::Tertiary => $plan !== null ? $plan->tertiary_bps : 0,
                AllocationCategory::Debt => $plan !== null ? $plan->debt_bps : 0,
                AllocationCategory::Savings => $plan !== null ? $plan->savings_bps : 0,
                AllocationCategory::Social => $plan !== null ? $plan->social_bps : 0,
            };

            $target = (int) floor(($incomeBase * $bps) / 10000);
            $totalTarget += $target;

            $used = $this->calculateUsedAmount($household, $cat, $period);
            $remaining = $target - $used;

            $allocationsData[$cat->value] = [
                'key' => $cat->value,
                'label' => $cat->label(),
                'bps' => $bps,
                'percentage' => $bps / 100,
                'target' => $target,
                'used' => $used,
                'remaining' => $remaining,
            ];
        }

        $unallocatedTarget = max(0, $incomeBase - $totalTarget);

        // Debt ratio calculation (US-FIN-04A AC5)
        $debtRatio = null;
        $debtRatioWarning = false;

        if ($incomeBase > 0) {
            $totalDebtPlanInPeriod = (int) PlannedItem::query()
                ->where('household_id', $household->id)
                ->where('period', $periodKey)
                ->where('direction', Direction::Expense)
                ->whereHas('category', function ($q) {
                    $q->where('default_allocation', AllocationCategory::Debt->value)
                        ->orWhere('system_key', 'loan_installment');
                })
                ->sum('amount');

            $debtRatio = round(($totalDebtPlanInPeriod / $incomeBase) * 100, 1);
            $debtRatioWarning = $debtRatio > 30.0;
        }

        return [
            'plan' => $plan,
            'income_base' => $incomeBase,
            'unallocated_target' => $unallocatedTarget,
            'debt_ratio' => $debtRatio,
            'debt_ratio_warning' => $debtRatioWarning,
            'allocations' => $allocationsData,
        ];
    }

    /**
     * Calculates the actual used/placed amount for an allocation category in a period.
     * BR-FIN-032: Expenses with allocation snapshot + net savings transfers.
     */
    public function calculateUsedAmount(Household $household, AllocationCategory $category, Period $period): int
    {
        $startDate = $period->start->toDateString();
        $endDate = $period->end->toDateString();

        // 1. Regular expense transactions with this allocation snapshot
        $expenses = (int) Transaction::query()
            ->where('household_id', $household->id)
            ->where('type', TransactionType::Expense)
            ->where('allocation', $category)
            ->whereBetween('occurred_on', [$startDate, $endDate])
            ->sum('amount');

        if ($category !== AllocationCategory::Savings) {
            return $expenses;
        }

        // 2. For savings: also add net savings transfers
        $savingsTransfers = (int) Transaction::query()
            ->where('household_id', $household->id)
            ->where('type', TransactionType::Transfer)
            ->where('allocation', AllocationCategory::Savings)
            ->whereNull('allocation_reversal_of_id')
            ->whereBetween('occurred_on', [$startDate, $endDate])
            ->sum('amount');

        $reversals = (int) Transaction::query()
            ->where('household_id', $household->id)
            ->where('type', TransactionType::Transfer)
            ->where('allocation', AllocationCategory::Savings)
            ->whereNotNull('allocation_reversal_of_id')
            ->whereBetween('occurred_on', [$startDate, $endDate])
            ->sum('amount');

        return $expenses + max(0, $savingsTransfers - $reversals);
    }
}
