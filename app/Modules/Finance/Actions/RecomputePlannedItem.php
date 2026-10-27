<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Enums\LoanStatus;
use App\Modules\Finance\Enums\PlannedSource;
use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Events\LoanPaidOff;
use App\Modules\Finance\Events\PlannedItemSettled;
use App\Modules\Finance\Models\Loan;
use App\Modules\Finance\Models\PlannedItem;
use App\Modules\Finance\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class RecomputePlannedItem
{
    /**
     * Recalculates paid_amount and status for a PlannedItem based on linked transactions.
     */
    public function execute(PlannedItem $item): PlannedItem
    {
        return DB::transaction(function () use ($item): PlannedItem {
            // Lock row for update
            /** @var PlannedItem $freshItem */
            $freshItem = PlannedItem::query()->lockForUpdate()->findOrFail($item->id);

            // If item was marked paid before tracking, preserve it
            if ($freshItem->paid_before_tracking) {
                return $freshItem;
            }

            $paidAmount = (int) Transaction::query()
                ->where('planned_item_id', $freshItem->id)
                ->sum('amount');

            $previousStatus = $freshItem->status;
            $newStatus = $previousStatus;
            $settledAt = $freshItem->settled_at;

            if ($paidAmount >= $freshItem->amount) {
                $newStatus = PlannedStatus::Paid;
                if ($settledAt === null) {
                    $settledAt = CarbonImmutable::now();
                }
            } elseif ($paidAmount > 0) {
                $newStatus = PlannedStatus::Partial;
                $settledAt = null;
            } else {
                // If 0, only revert to Planned if it was previously Paid or Partial
                if ($previousStatus === PlannedStatus::Paid || $previousStatus === PlannedStatus::Partial) {
                    $newStatus = PlannedStatus::Planned;
                    $settledAt = null;
                }
            }

            $freshItem->paid_amount = $paidAmount;
            $freshItem->status = $newStatus;
            $freshItem->settled_at = $settledAt;
            $freshItem->save();

            // Emit PlannedItemSettled if newly paid
            if ($newStatus === PlannedStatus::Paid && $previousStatus !== PlannedStatus::Paid) {
                PlannedItemSettled::dispatch($freshItem->id);
            }

            // Check loan auto paid_off if source is loan
            if ($freshItem->source_type === PlannedSource::Loan && $freshItem->source_id !== null) {
                $this->checkLoanCompletion($freshItem->source_id);
            }

            return $freshItem;
        });
    }

    private function checkLoanCompletion(string $loanId): void
    {
        $loan = Loan::query()->find($loanId);
        if ($loan === null) {
            return;
        }

        $allInstallments = PlannedItem::query()
            ->where('source_type', PlannedSource::Loan)
            ->where('source_id', $loanId)
            ->get();

        if ($allInstallments->isEmpty()) {
            return;
        }

        $allPaid = $allInstallments->every(
            fn (PlannedItem $item) => $item->status === PlannedStatus::Paid || $item->paid_before_tracking
        );

        if ($allPaid && $loan->status !== LoanStatus::PaidOff) {
            $loan->status = LoanStatus::PaidOff;
            $loan->paid_off_at = CarbonImmutable::now();
            $loan->save();

            LoanPaidOff::dispatch($loan->id);
        } elseif (! $allPaid && $loan->status === LoanStatus::PaidOff) {
            // Revert back to active if an installment was undone/reduced
            $loan->status = LoanStatus::Active;
            $loan->paid_off_at = null;
            $loan->save();
        }
    }
}
