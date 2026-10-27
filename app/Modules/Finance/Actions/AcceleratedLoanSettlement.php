<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Models\User;
use App\Modules\Finance\Enums\LoanDirection;
use App\Modules\Finance\Enums\LoanStatus;
use App\Modules\Finance\Enums\PlannedSource;
use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Events\LoanPaidOff;
use App\Modules\Finance\Events\TransactionRecorded;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Loan;
use App\Modules\Finance\Models\PlannedItem;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Services\AccountBalanceService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class AcceleratedLoanSettlement
{
    public function __construct(
        private readonly AccountBalanceService $balanceService,
    ) {
    }

    /**
     * Executes early/accelerated settlement for a Loan.
     * Records one payment transaction, cancels remaining unpaid installments, and marks loan paid off.
     */
    public function execute(
        Loan $loan,
        int $settlementAmount,
        Account $account,
        CarbonImmutable $settlementDate,
        ?string $note = null,
        ?User $actor = null,
    ): Transaction {
        if ($loan->status !== LoanStatus::Active) {
            throw new InvalidArgumentException('Only active loans can be settled early.');
        }

        if ($settlementAmount <= 0) {
            throw new InvalidArgumentException('Settlement amount must be greater than zero.');
        }

        return DB::transaction(function () use ($loan, $settlementAmount, $account, $settlementDate, $note, $actor): Transaction {
            $type = $loan->direction === LoanDirection::Payable
                ? TransactionType::Expense
                : TransactionType::Income;

            // 1. Record single settlement transaction
            $transaction = new Transaction();
            $transaction->household_id = $loan->household_id;
            $transaction->type = $type;
            $transaction->account_id = $account->id;
            $transaction->category_id = $loan->category_id;
            $transaction->amount = $settlementAmount;
            $transaction->occurred_on = $settlementDate;
            $transaction->payee = $loan->counterparty;
            $transaction->note = $note ?? __('finance.accelerated_settlement_note', ['name' => $loan->name]);
            $transaction->created_by = $actor?->id;
            $transaction->save();

            $this->balanceService->bustCache($account->id);
            TransactionRecorded::dispatch($transaction->id);

            // 2. Cancel all remaining unpaid installments
            PlannedItem::query()
                ->where('source_type', PlannedSource::Loan)
                ->where('source_id', $loan->id)
                ->whereIn('status', [PlannedStatus::Planned, PlannedStatus::Partial])
                ->update([
                    'status' => PlannedStatus::Cancelled,
                    'status_reason' => __('finance.accelerated_settlement_reason'),
                ]);

            // 3. Mark loan as paid_off
            $loan->status = LoanStatus::PaidOff;
            $loan->paid_off_at = $settlementDate;
            $loan->save();

            LoanPaidOff::dispatch($loan->id);

            return $transaction;
        });
    }
}
