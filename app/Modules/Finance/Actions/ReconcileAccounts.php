<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Core\Models\Household;
use App\Models\User;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class ReconcileAccounts
{
    public function __construct(
        private readonly AdjustAccountBalance $adjustAction,
    ) {
    }

    /**
     * Previews account balance differences as of the given date.
     *
     * @param array<string, int> $actualBalances [account_id => actual_balance_int]
     * @return array<string, array{account: Account, calculated: int, actual: int, diff: int}>
     */
    public function preview(
        Household $household,
        CarbonImmutable $date,
        array $actualBalances
    ): array {
        $accounts = Account::query()
            ->where('household_id', $household->id)
            ->whereNull('archived_at')
            ->get();

        $preview = [];

        foreach ($accounts as $account) {
            $accountId = $account->id;
            $actual = $actualBalances[$accountId] ?? null;

            // Historical balance calculation as of $date
            $calculated = $this->calculateBalanceAsOfDate($account, $date);

            if ($actual !== null) {
                $preview[$accountId] = [
                    'account' => $account,
                    'calculated' => $calculated,
                    'actual' => (int) $actual,
                    'diff' => (int) $actual - $calculated,
                ];
            }
        }

        return $preview;
    }

    /**
     * Executes the reconciliation adjustments after user confirmation.
     *
     * @param array<string, int> $actualBalances
     * @return list<Transaction>
     */
    public function confirm(
        Household $household,
        CarbonImmutable $date,
        array $actualBalances,
        ?User $actor = null
    ): array {
        return DB::transaction(function () use ($household, $date, $actualBalances, $actor): array {
            $createdTransactions = [];
            $preview = $this->preview($household, $date, $actualBalances);

            foreach ($preview as $item) {
                if ($item['diff'] !== 0) {
                    $tx = $this->adjustAction->execute(
                        $item['account'],
                        $item['actual'],
                        $date,
                        __('finance.reconciliation_adjustment_note', ['date' => $date->toDateString()]),
                        $actor
                    );

                    if ($tx !== null) {
                        $createdTransactions[] = $tx;
                    }
                }
            }

            return $createdTransactions;
        });
    }

    private function calculateBalanceAsOfDate(Account $account, CarbonImmutable $date): int
    {
        $opening = $account->opening_balance;
        $dateStr = $date->toDateString();

        $income = (int) Transaction::query()
            ->where('account_id', $account->id)
            ->where('type', TransactionType::Income)
            ->where('occurred_on', '<=', $dateStr)
            ->sum('amount');

        $expense = (int) Transaction::query()
            ->where('account_id', $account->id)
            ->where('type', TransactionType::Expense)
            ->where('occurred_on', '<=', $dateStr)
            ->sum('amount');

        $transferOut = (int) Transaction::query()
            ->where('account_id', $account->id)
            ->where('type', TransactionType::Transfer)
            ->where('occurred_on', '<=', $dateStr)
            ->sum('amount');

        $transferIn = (int) Transaction::query()
            ->where('to_account_id', $account->id)
            ->where('type', TransactionType::Transfer)
            ->where('occurred_on', '<=', $dateStr)
            ->sum('amount');

        return $opening + $income - $expense - $transferOut + $transferIn;
    }
}
