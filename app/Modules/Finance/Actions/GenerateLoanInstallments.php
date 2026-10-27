<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Core\Support\PeriodResolver;
use App\Modules\Finance\Enums\Direction;
use App\Modules\Finance\Enums\LoanDirection;
use App\Modules\Finance\Enums\LoanFrequency;
use App\Modules\Finance\Enums\LoanStatus;
use App\Modules\Finance\Enums\PlannedSource;
use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Events\LoanPaidOff;
use App\Modules\Finance\Models\Loan;
use App\Modules\Finance\Models\PlannedItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class GenerateLoanInstallments
{
    /**
     * Generates all installment PlannedItems for a Loan.
     *
     * @param int $paidBeforeCount Number of installments already paid prior to tracking
     * @return list<PlannedItem>
     */
    public function execute(Loan $loan, int $paidBeforeCount = 0): array
    {
        $n = $loan->installment_count;
        $instAmount = $loan->installment_amount;
        $totalPayable = $loan->total_payable;

        if ($n < 1) {
            throw new InvalidArgumentException('Installment count must be at least 1.');
        }

        // BR-FIN-041: Last installment absorbs difference
        $lastAmount = $totalPayable - ($instAmount * ($n - 1));
        if ($lastAmount <= 0) {
            throw new InvalidArgumentException('Total bayar terlalu kecil untuk jumlah cicilan.');
        }

        if ($paidBeforeCount > $n) {
            throw new InvalidArgumentException('Cicilan sudah dibayar tidak boleh melebihi jumlah cicilan.');
        }

        return DB::transaction(function () use ($loan, $n, $instAmount, $lastAmount, $paidBeforeCount): array {
            $household = $loan->household;
            $resolver = PeriodResolver::for($household);
            $items = [];

            $direction = $loan->direction === LoanDirection::Payable
                ? Direction::Expense
                : Direction::Income;

            $firstDueDate = $loan->first_due_date;
            $targetDay = $firstDueDate->day;

            for ($k = 1; $k <= $n; $k++) {
                $amount = ($k === $n) ? $lastAmount : $instAmount;
                $dueDate = $this->calculateDueDate($loan, $firstDueDate, $targetDay, $k);
                $period = $resolver->forDate($dueDate)->key;
                $isPaidBefore = ($k <= $paidBeforeCount);

                $item = PlannedItem::query()
                    ->where('source_type', PlannedSource::Loan)
                    ->where('source_id', $loan->id)
                    ->where('sequence', $k)
                    ->first();

                if ($item === null) {
                    $item = new PlannedItem();
                    $item->household_id = $loan->household_id;
                    $item->source_type = PlannedSource::Loan;
                    $item->source_id = $loan->id;
                    $item->sequence = $k;
                }

                $item->name = "{$loan->name} – Cicilan {$k}/{$n}";
                $item->direction = $direction;
                $item->category_id = $loan->category_id;
                $item->account_id = $loan->account_id;
                $item->amount = $amount;
                $item->due_date = $dueDate;
                $item->period = $period;

                if ($isPaidBefore) {
                    $item->status = PlannedStatus::Paid;
                    $item->paid_before_tracking = true;
                    $item->paid_amount = $amount;
                    $item->settled_at = CarbonImmutable::now();
                } else {
                    // Only reset if previously planned or brand new
                    if ($item->status === PlannedStatus::Planned || ! $item->exists) {
                        $item->status = PlannedStatus::Planned;
                        $item->paid_before_tracking = false;
                        $item->paid_amount = 0;
                        $item->settled_at = null;
                    }
                }

                $item->save();
                $items[] = $item;
            }

            if ($paidBeforeCount === $n) {
                $loan->status = LoanStatus::PaidOff;
                $loan->paid_off_at = CarbonImmutable::now();
                $loan->save();

                LoanPaidOff::dispatch($loan->id);
            }

            return $items;
        });
    }

    private function calculateDueDate(Loan $loan, CarbonImmutable $firstDueDate, int $targetDay, int $k): CarbonImmutable
    {
        if ($k === 1) {
            return $firstDueDate;
        }

        $step = $k - 1;

        return match ($loan->frequency) {
            LoanFrequency::Monthly => $this->calculateMonthlyDueDate($firstDueDate, $targetDay, $step),
            LoanFrequency::Weekly => $firstDueDate->addWeeks($step),
            LoanFrequency::Once => $firstDueDate,
        };
    }

    private function calculateMonthlyDueDate(CarbonImmutable $firstDueDate, int $targetDay, int $monthsToAdd): CarbonImmutable
    {
        // BR-FIN-040: Target day or last day of that month (e.g. 31 Jan -> 28/29 Feb)
        $monthTarget = $firstDueDate->startOfMonth()->addMonthsNoOverflow($monthsToAdd);
        $daysInMonth = $monthTarget->daysInMonth;
        $day = min($targetDay, $daysInMonth);

        return $monthTarget->day($day);
    }
}
