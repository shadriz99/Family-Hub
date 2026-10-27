<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Models\Household;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Support\Facades\Cache;

final class AccountBalanceService
{
    public const CACHE_PREFIX = 'fin:balance:';
    public const CACHE_TTL_SECONDS = 86400; // 24 hours

    /**
     * Get calculated balance for a given account (cached).
     */
    public function getBalance(Account|string $account): int
    {
        $accountId = $account instanceof Account ? $account->id : $account;
        $cacheKey = self::CACHE_PREFIX . $accountId;

        return (int) Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($account, $accountId): int {
            $accountModel = $account instanceof Account
                ? $account
                : Account::query()->find($accountId);

            if ($accountModel === null) {
                return 0;
            }

            $openingBalance = $accountModel->opening_balance;

            // Income where account_id = $accountId
            $income = (int) Transaction::query()
                ->where('account_id', $accountId)
                ->where('type', TransactionType::Income)
                ->sum('amount');

            // Expense where account_id = $accountId
            $expense = (int) Transaction::query()
                ->where('account_id', $accountId)
                ->where('type', TransactionType::Expense)
                ->sum('amount');

            // Transfer out where account_id = $accountId
            $transferOut = (int) Transaction::query()
                ->where('account_id', $accountId)
                ->where('type', TransactionType::Transfer)
                ->sum('amount');

            // Transfer in where to_account_id = $accountId
            $transferIn = (int) Transaction::query()
                ->where('to_account_id', $accountId)
                ->where('type', TransactionType::Transfer)
                ->sum('amount');

            return $openingBalance + $income - $expense - $transferOut + $transferIn;
        });
    }

    /**
     * Invalidate cached balance for one or more accounts.
     */
    public function bustCache(string ...$accountIds): void
    {
        foreach ($accountIds as $id) {
            if ($id !== '') {
                Cache::forget(self::CACHE_PREFIX . $id);
            }
        }
    }

    /**
     * Operational balance: cash, bank, ewallet, credit_card (with include_in_total=true).
     * BR-FIN-005: credit_card negative balance reduces operational balance.
     */
    public function getOperationalBalance(Household $household): int
    {
        $accounts = Account::query()
            ->where('household_id', $household->id)
            ->whereNull('archived_at')
            ->where('include_in_total', true)
            ->whereIn('type', [
                AccountType::Cash,
                AccountType::Bank,
                AccountType::EWallet,
                AccountType::CreditCard,
            ])
            ->get();

        $total = 0;
        foreach ($accounts as $account) {
            $total += $this->getBalance($account);
        }

        return $total;
    }

    /**
     * Savings balance: type savings (with include_in_total=true).
     */
    public function getSavingsBalance(Household $household): int
    {
        $accounts = Account::query()
            ->where('household_id', $household->id)
            ->whereNull('archived_at')
            ->where('include_in_total', true)
            ->where('type', AccountType::Savings)
            ->get();

        $total = 0;
        foreach ($accounts as $account) {
            $total += $this->getBalance($account);
        }

        return $total;
    }

    /**
     * Total combined balance = operational + savings (BR-FIN-005).
     */
    public function getTotalCombinedBalance(Household $household): int
    {
        return $this->getOperationalBalance($household) + $this->getSavingsBalance($household);
    }
}
