<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Core\Models\Household;
use App\Core\Recurrence\Recurrence;
use App\Core\Support\PeriodResolver;
use App\Modules\Finance\Enums\PlannedSource;
use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Models\PlannedItem;
use App\Modules\Finance\Models\RecurringBill;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class GeneratePlannedItemsFromRecurringBills
{
    /**
     * Generates PlannedItems for active recurring bills up to the plan horizon.
     *
     * @return int Total number of new PlannedItems created
     */
    public function execute(Household $household, ?RecurringBill $specificBill = null): int
    {
        $resolver = PeriodResolver::for($household);
        $currentPeriod = $resolver->current();

        // 3 periods ahead horizon
        $horizonPeriod = $currentPeriod;
        $horizonCount = (int) config('finance.plan_horizon_periods', 3);
        for ($i = 0; $i < $horizonCount; $i++) {
            $horizonPeriod = $resolver->next($horizonPeriod);
        }

        $horizonEnd = $horizonPeriod->end->toDateString();
        $horizonStartDate = $currentPeriod->start;
        $horizonEndDate = $horizonPeriod->end;

        $billsQuery = RecurringBill::query()
            ->where('household_id', $household->id);

        if ($specificBill !== null) {
            $billsQuery->where('id', $specificBill->id);
        } else {
            $billsQuery->where('is_active', true);
        }

        $bills = $billsQuery->get();
        $totalCreated = 0;

        foreach ($bills as $bill) {
            $totalCreated += $this->processBill($bill, $household, $resolver, $horizonStartDate, $horizonEndDate);
        }

        return $totalCreated;
    }

    private function processBill(
        RecurringBill $bill,
        Household $household,
        PeriodResolver $resolver,
        CarbonImmutable $horizonStart,
        CarbonImmutable $horizonEnd
    ): int {
        return DB::transaction(function () use ($bill, $household, $resolver, $horizonStart, $horizonEnd): int {
            // If bill is deactivated: cancel future planned items
            if (! $bill->is_active) {
                PlannedItem::query()
                    ->where('source_type', PlannedSource::RecurringBill)
                    ->where('source_id', $bill->id)
                    ->where('due_date', '>=', now()->toDateString())
                    ->where('status', PlannedStatus::Planned)
                    ->update([
                        'status' => PlannedStatus::Cancelled,
                        'status_reason' => __('finance.recurring_bill_deactivated'),
                    ]);

                return 0;
            }

            $from = $bill->dtstart->greaterThan($horizonStart) ? $bill->dtstart : $horizonStart;
            $to = $bill->until !== null && $bill->until->lessThan($horizonEnd)
                ? $bill->until
                : $horizonEnd;

            if ($from->greaterThan($to)) {
                return 0;
            }

            $occurrences = Recurrence::occurrencesBetween(
                $bill->rrule,
                $bill->dtstart,
                $from,
                $to
            );

            $createdCount = 0;

            foreach ($occurrences as $occurrence) {
                $dueDate = $occurrence->startOfDay();
                $dueDateStr = $dueDate->toDateString();
                $period = $resolver->forDate($dueDate)->key;

                $existing = PlannedItem::query()
                    ->where('household_id', $household->id)
                    ->where('source_type', PlannedSource::RecurringBill)
                    ->where('source_id', $bill->id)
                    ->whereDate('due_date', $dueDateStr)
                    ->first();

                if ($existing === null) {
                    $item = new PlannedItem();
                    $item->household_id = $household->id;
                    $item->direction = $bill->direction;
                    $item->name = $bill->name;
                    $item->category_id = $bill->category_id;
                    $item->account_id = $bill->account_id;
                    $item->amount = $bill->amount;
                    $item->due_date = $dueDate;
                    $item->period = $period;
                    $item->status = PlannedStatus::Planned;
                    $item->source_type = PlannedSource::RecurringBill;
                    $item->source_id = $bill->id;
                    $item->save();

                    $createdCount++;
                } else {
                    // BR-FIN-022: Only update if status is Planned
                    if ($existing->status === PlannedStatus::Planned) {
                        $existing->name = $bill->name;
                        $existing->direction = $bill->direction;
                        $existing->category_id = $bill->category_id;
                        $existing->account_id = $bill->account_id;
                        $existing->amount = $bill->amount;
                        $existing->period = $period;
                        $existing->save();
                    }
                }
            }

            $bill->generated_until = $to;
            $bill->save();

            return $createdCount;
        });
    }
}
