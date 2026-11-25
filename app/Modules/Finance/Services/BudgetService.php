<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Models\Household;
use App\Core\Support\Period;
use App\Core\Support\PeriodResolver;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Events\BudgetThresholdReached;
use App\Modules\Finance\Models\AllocationPlan;
use App\Modules\Finance\Models\Budget;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Models\TransactionSplit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class BudgetService
{
    /**
     * Get total income for the period (actual income or fallback to allocation plan income base).
     */
    public function getTotalIncome(Household $household, Period $period): int
    {
        $actual = (int) Transaction::query()
            ->where('household_id', $household->id)
            ->where('type', TransactionType::Income)
            ->whereBetween('occurred_on', [
                $period->start->toDateString(),
                $period->end->toDateString(),
            ])
            ->sum('amount');

        if ($actual > 0) {
            return $actual;
        }

        $plan = AllocationPlan::query()
            ->where('household_id', $household->id)
            ->where('period', $period->key)
            ->first();

        return $plan !== null ? $plan->income_base : 0;
    }

    /**
     * Get budget rows for parent expense categories in the given period.
     *
     * @return Collection<int, array{
     *     category: Category,
     *     budget: Budget|null,
     *     budget_percentage: int,
     *     budget_amount: int,
     *     spent_amount: int,
     *     remaining_amount: int,
     *     percentage: float,
     *     used_percentage: float,
     * }>
     */
    public function getBudgetSummary(Household $household, string $periodKey): Collection
    {
        $resolver = PeriodResolver::for($household);
        $period = $resolver->fromKey($periodKey);
        $totalIncome = $this->getTotalIncome($household, $period);

        $parentCategories = Category::query()
            ->where('household_id', $household->id)
            ->whereNull('parent_id')
            ->where('type', CategoryType::Expense)
            ->whereNotIn('system_key', ['balance_adjustment', 'loan_disbursement_out'])
            ->whereNull('archived_at')
            ->orderBy('sort_order')
            ->get();

        $budgets = Budget::query()
            ->where('household_id', $household->id)
            ->where('period', $periodKey)
            ->get()
            ->keyBy('category_id');

        return $parentCategories->map(function (Category $category) use ($period, $budgets, $totalIncome): array {
            $budget = $budgets->get($category->id);
            $budgetPercentage = $budget ? $budget->percentage : 0;

            if ($budgetPercentage > 0 && $totalIncome > 0) {
                $budgetAmount = (int) floor(($totalIncome * $budgetPercentage) / 100);
            } else {
                $budgetAmount = $budget ? $budget->amount : 0;
            }

            $spentAmount = $this->calculateSpent($category, $period);
            $remaining = $budgetAmount - $spentAmount;
            $percentage = $budgetAmount > 0 ? round(($spentAmount / $budgetAmount) * 100, 1) : 0.0;

            return [
                'category' => $category,
                'budget' => $budget,
                'budget_percentage' => $budgetPercentage,
                'budget_amount' => $budgetAmount,
                'spent_amount' => $spentAmount,
                'remaining_amount' => $remaining,
                'percentage' => $percentage,
                'used_percentage' => $percentage,
            ];
        });
    }

    /**
     * BR-FIN-030: Spent = Σ expense kategori tsb + sub-kategorinya dalam periode,
     * TANPA transaksi yang memiliki planned_item_id.
     */
    public function calculateSpent(Category $category, Period $period): int
    {
        $categoryIds = [$category->id];
        $childIds = Category::query()
            ->where('parent_id', $category->id)
            ->pluck('id')
            ->all();

        $allIds = array_merge($categoryIds, $childIds);

        $directSum = (int) Transaction::query()
            ->whereIn('category_id', $allIds)
            ->where('type', TransactionType::Expense)
            ->whereNull('planned_item_id')
            ->whereDoesntHave('splits')
            ->whereBetween('occurred_on', [
                $period->start->toDateString(),
                $period->end->toDateString(),
            ])
            ->sum('amount');

        $splitSum = (int) TransactionSplit::query()
            ->whereIn('category_id', $allIds)
            ->whereHas('transaction', function ($q) use ($period): void {
                $q->where('type', TransactionType::Expense)
                    ->whereNull('planned_item_id')
                    ->whereBetween('occurred_on', [
                        $period->start->toDateString(),
                        $period->end->toDateString(),
                    ]);
            })
            ->sum('amount');

        return $directSum + $splitSum;
    }

    /**
     * Checks and sends 80% and 100% threshold alerts.
     * BR-FIN-031: Sent once per threshold per period per category;
     * reset if budget is increased so that percentage drops below threshold.
     */
    public function checkThresholds(Budget $budget): void
    {
        if ($budget->amount <= 0) {
            return;
        }

        $resolver = PeriodResolver::for($budget->household);
        $period = $resolver->fromKey($budget->period);
        $spent = $this->calculateSpent($budget->category, $period);
        $ratio = $spent / $budget->amount;

        // Reset if ratio drops below threshold
        if ($ratio < 0.80 && $budget->alert_80_sent_at !== null) {
            $budget->alert_80_sent_at = null;
            $budget->save();
        }

        if ($ratio < 1.00 && $budget->alert_100_sent_at !== null) {
            $budget->alert_100_sent_at = null;
            $budget->save();
        }

        // Trigger 80% alert
        if ($ratio >= 0.80 && $budget->alert_80_sent_at === null) {
            $budget->alert_80_sent_at = CarbonImmutable::now();
            $budget->save();
            BudgetThresholdReached::dispatch($budget->id, 80);
        }

        // Trigger 100% alert
        if ($ratio >= 1.00 && $budget->alert_100_sent_at === null) {
            $budget->alert_100_sent_at = CarbonImmutable::now();
            $budget->save();
            BudgetThresholdReached::dispatch($budget->id, 100);
        }
    }

    /**
     * Copy budgets from previous period for any parent category missing a budget in target period.
     *
     * @return int Number of budgets copied
     */
    public function copyFromPreviousPeriod(Household $household, string $targetPeriod, string $previousPeriod): int
    {
        return DB::transaction(function () use ($household, $targetPeriod, $previousPeriod): int {
            $prevBudgets = Budget::query()
                ->where('household_id', $household->id)
                ->where('period', $previousPeriod)
                ->get();

            $copied = 0;
            foreach ($prevBudgets as $prev) {
                $exists = Budget::query()
                    ->where('household_id', $household->id)
                    ->where('category_id', $prev->category_id)
                    ->where('period', $targetPeriod)
                    ->exists();

                if (! $exists) {
                    $newBudget = new Budget();
                    $newBudget->household_id = $household->id;
                    $newBudget->category_id = $prev->category_id;
                    $newBudget->period = $targetPeriod;
                    $newBudget->amount = $prev->amount;
                    $newBudget->save();

                    $copied++;
                }
            }

            return $copied;
        });
    }
}
