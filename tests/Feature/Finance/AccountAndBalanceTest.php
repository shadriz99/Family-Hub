<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Core\Actions\CreateHousehold;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Finance\Actions\AdjustAccountBalance;
use App\Modules\Finance\Actions\ReconcileAccounts;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Services\AccountBalanceService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::create([
        'name' => 'Shadri Zulkurniadi',
        'email' => 'shadri@example.com',
        'password' => bcrypt('password123'),
    ]);

    $this->household = app(CreateHousehold::class)->handle(
        $this->user,
        'Keluarga Shadri',
        1,
        'Asia/Jakarta'
    );
});

it('calculates account balance accurately through data-driven income, expense, transfers, and credit card scenarios', function (
    AccountType $type,
    int $openingBalance,
    int $income,
    int $expense,
    int $transferOut,
    int $transferIn,
    int $expectedBalance
): void {
    $context = app(HouseholdContext::class);
    $balanceService = app(AccountBalanceService::class);

    $context->runInContext($this->household, function () use (
        $type,
        $openingBalance,
        $income,
        $expense,
        $transferOut,
        $transferIn,
        $expectedBalance,
        $balanceService
    ): void {
        $account = Account::create([
            'household_id' => $this->household->id,
            'name' => "Test Account {$type->value}",
            'type' => $type,
            'opening_balance' => $openingBalance,
            'opening_date' => now()->subDays(30),
            'color' => '#10b981',
            'icon' => 'wallet',
        ]);

        $otherAccount = Account::create([
            'household_id' => $this->household->id,
            'name' => 'Other Counterpart Account',
            'type' => AccountType::Bank,
            'opening_balance' => 10000000,
            'opening_date' => now()->subDays(30),
            'color' => '#3b82f6',
            'icon' => 'building-library',
        ]);

        $categoryExpense = Category::where('household_id', $this->household->id)
            ->where('type', CategoryType::Expense)
            ->firstOrFail();

        $categoryIncome = Category::where('household_id', $this->household->id)
            ->where('type', CategoryType::Income)
            ->firstOrFail();

        if ($income > 0) {
            Transaction::create([
                'household_id' => $this->household->id,
                'type' => TransactionType::Income,
                'account_id' => $account->id,
                'category_id' => $categoryIncome->id,
                'amount' => $income,
                'occurred_on' => now(),
            ]);
        }

        if ($expense > 0) {
            Transaction::create([
                'household_id' => $this->household->id,
                'type' => TransactionType::Expense,
                'account_id' => $account->id,
                'category_id' => $categoryExpense->id,
                'amount' => $expense,
                'occurred_on' => now(),
            ]);
        }

        if ($transferOut > 0) {
            Transaction::create([
                'household_id' => $this->household->id,
                'type' => TransactionType::Transfer,
                'account_id' => $account->id,
                'to_account_id' => $otherAccount->id,
                'amount' => $transferOut,
                'occurred_on' => now(),
            ]);
        }

        if ($transferIn > 0) {
            Transaction::create([
                'household_id' => $this->household->id,
                'type' => TransactionType::Transfer,
                'account_id' => $otherAccount->id,
                'to_account_id' => $account->id,
                'amount' => $transferIn,
                'occurred_on' => now(),
            ]);
        }

        $balanceService->bustCache($account->id);
        $calculated = $balanceService->getBalance($account);

        expect($calculated)->toBe($expectedBalance);
    });
})->with([
    'bank normal: opening 1jt + income 2jt - expense 500rb = 2.5jt' => [
        AccountType::Bank, 1000000, 2000000, 500000, 0, 0, 2500000,
    ],
    'cash with transfer out: opening 500rb - transferOut 200rb = 300rb' => [
        AccountType::Cash, 500000, 0, 0, 200000, 0, 300000,
    ],
    'ewallet with transfer in: opening 50rb + transferIn 150rb - expense 75rb = 125rb' => [
        AccountType::EWallet, 50000, 0, 75000, 0, 150000, 125000,
    ],
    'credit card negative opening bill: opening -1.5jt + payment transferIn 1.5jt - expense 400rb = -400rb' => [
        AccountType::CreditCard, -1500000, 0, 400000, 0, 1500000, -400000,
    ],
    'savings balance isolated' => [
        AccountType::Savings, 5000000, 0, 0, 0, 1000000, 6000000,
    ],
]);

it('reconciles account balances at end of period without modifying budget and creating transactions only after user confirmation', function (): void {
    $context = app(HouseholdContext::class);
    $reconciler = app(ReconcileAccounts::class);
    $balanceService = app(AccountBalanceService::class);

    $context->runInContext($this->household, function () use ($reconciler, $balanceService): void {
        $account = Account::create([
            'household_id' => $this->household->id,
            'name' => 'BCA Rekonsiliasi',
            'type' => AccountType::Bank,
            'opening_balance' => 5000000,
            'opening_date' => now()->subDays(10),
            'color' => '#10b981',
            'icon' => 'wallet',
        ]);

        $date = CarbonImmutable::now();

        // Actual bank balance is 5.250.000 (calculated is 5.000.000) -> diff +250.000
        $preview = $reconciler->preview($this->household, $date, [
            $account->id => 5250000,
        ]);

        expect($preview)->toHaveKey($account->id);
        expect($preview[$account->id]['calculated'])->toBe(5000000);
        expect($preview[$account->id]['actual'])->toBe(5250000);
        expect($preview[$account->id]['diff'])->toBe(250000);

        // Before confirmation: no adjustment transactions exist
        $txCountBefore = Transaction::where('account_id', $account->id)->count();
        expect($txCountBefore)->toBe(0);

        // Confirm reconciliation
        $created = $reconciler->confirm($this->household, $date, [
            $account->id => 5250000,
        ], $this->user);

        expect($created)->toHaveCount(1);
        expect($created[0]->amount)->toBe(250000);
        expect($created[0]->type)->toBe(TransactionType::Income);

        $balanceService->bustCache($account->id);
        expect($balanceService->getBalance($account))->toBe(5250000);
    });
});

it('adjusts account balance directly via AdjustAccountBalance action', function (): void {
    $context = app(HouseholdContext::class);
    $adjuster = app(AdjustAccountBalance::class);
    $balanceService = app(AccountBalanceService::class);

    $context->runInContext($this->household, function () use ($adjuster, $balanceService): void {
        $account = Account::create([
            'household_id' => $this->household->id,
            'name' => 'Mandiri Penyesuaian',
            'type' => AccountType::Bank,
            'opening_balance' => 3000000,
            'opening_date' => now()->subDays(5),
            'color' => '#10b981',
            'icon' => 'wallet',
        ]);

        // Adjust downwards from 3jt to 2.8jt -> expense 200rb
        $tx = $adjuster->execute($account, 2800000, CarbonImmutable::now(), 'Biaya materai', $this->user);

        expect($tx)->not->toBeNull();
        expect($tx?->type)->toBe(TransactionType::Expense);
        expect($tx?->amount)->toBe(200000);

        $balanceService->bustCache($account->id);
        expect($balanceService->getBalance($account))->toBe(2800000);
    });
});
