<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Models\User;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Events\TransactionRecorded;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Services\AccountBalanceService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class AdjustAccountBalance
{
    public function __construct(
        private readonly AccountBalanceService $balanceService,
    ) {
    }

    /**
     * Adjusts the account balance to match target balance by recording a system adjustment transaction.
     */
    public function execute(
        Account $account,
        int $targetBalance,
        CarbonImmutable $date,
        ?string $note = null,
        ?User $actor = null,
    ): ?Transaction {
        $currentBalance = $this->balanceService->getBalance($account);
        $diff = $targetBalance - $currentBalance;

        if ($diff === 0) {
            return null;
        }

        return DB::transaction(function () use ($account, $diff, $date, $note, $actor): Transaction {
            $isPositive = $diff > 0;
            $type = $isPositive ? TransactionType::Income : TransactionType::Expense;
            $categoryType = $isPositive ? CategoryType::Income : CategoryType::Expense;

            $category = Category::query()
                ->where('household_id', $account->household_id)
                ->where('system_key', 'balance_adjustment')
                ->where('type', $categoryType)
                ->first();

            if ($category === null) {
                throw new InvalidArgumentException("System category 'balance_adjustment' not found for type {$categoryType->value}.");
            }

            $transaction = new Transaction();
            $transaction->household_id = $account->household_id;
            $transaction->type = $type;
            $transaction->account_id = $account->id;
            $transaction->category_id = $category->id;
            $transaction->amount = abs($diff);
            $transaction->occurred_on = $date;
            $transaction->note = $note ?? __('finance.balance_adjustment_note');
            $transaction->created_by = $actor?->id;
            $transaction->save();

            $this->balanceService->bustCache($account->id);

            TransactionRecorded::dispatch($transaction->id);

            return $transaction;
        });
    }
}
