<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Core\Actions\CreateHousehold;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Finance\Actions\AcceleratedLoanSettlement;
use App\Modules\Finance\Actions\GenerateLoanInstallments;
use App\Modules\Finance\Actions\RecomputePlannedItem;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Enums\LoanDirection;
use App\Modules\Finance\Enums\LoanFrequency;
use App\Modules\Finance\Enums\LoanStatus;
use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Loan;
use App\Modules\Finance\Models\PlannedItem;
use App\Modules\Finance\Models\Transaction;
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
    app(HouseholdContext::class)->use($this->household);

    $this->account = Account::create([
        'household_id' => $this->household->id,
        'name' => 'BCA Syariah',
        'type' => AccountType::Bank,
        'opening_balance' => 25000000,
        'opening_date' => now()->subDays(60),
        'color' => '#10b981',
        'icon' => 'wallet',
    ]);

    $this->category = Category::where('household_id', $this->household->id)
        ->where('system_key', 'loan_installment')
        ->firstOrFail();
});

it('generates 12 monthly installments starting on Jan 31 clamping February to end of month and handles last installment absorbing rounding', function (): void {
    $context = app(HouseholdContext::class);
    $generator = app(GenerateLoanInstallments::class);

    $context->runInContext($this->household, function () use ($generator): void {
        $loan = Loan::create([
            'household_id' => $this->household->id,
            'direction' => LoanDirection::Payable,
            'name' => 'Kredit Motor',
            'counterparty' => 'BCA Finance',
            'principal' => 12000000,
            'installment_count' => 12,
            'installment_amount' => 1250000,
            'total_payable' => 15000000, // Total difference = 3jt
            'frequency' => LoanFrequency::Monthly,
            'first_due_date' => CarbonImmutable::create(2026, 1, 31),
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
        ]);

        $installments = $generator->execute($loan);

        expect($installments)->toHaveCount(12);

        // Installment 1: Jan 31
        expect($installments[0]->due_date->toDateString())->toBe('2026-01-31');
        expect($installments[0]->amount)->toBe(1250000);

        // Installment 2: Feb 28 (BR-FIN-040: clamps to end of month)
        expect($installments[1]->due_date->toDateString())->toBe('2026-02-28');

        // Installment 3: Mar 31
        expect($installments[2]->due_date->toDateString())->toBe('2026-03-31');

        // Installment 4: Apr 30
        expect($installments[3]->due_date->toDateString())->toBe('2026-04-30');

        // Total difference is exactly 3.000.000 without allocating to individual installments
        expect($loan->totalDifference())->toBe(3000000);
    });
});

it('handles paid_before_tracking, partial payments, accelerated early settlement, and auto paid_off status', function (): void {
    $context = app(HouseholdContext::class);
    $generator = app(GenerateLoanInstallments::class);
    $recompute = app(RecomputePlannedItem::class);
    $acceleratedSettlement = app(AcceleratedLoanSettlement::class);

    $context->runInContext($this->household, function () use ($generator, $recompute, $acceleratedSettlement): void {
        // Loan with 4 installments, 2 already paid prior to tracking
        $loan = Loan::create([
            'household_id' => $this->household->id,
            'direction' => LoanDirection::Payable,
            'name' => 'Pinjaman Darurat',
            'counterparty' => 'Keluarga',
            'principal' => 4000000,
            'installment_count' => 4,
            'installment_amount' => 1000000,
            'total_payable' => 4000000,
            'frequency' => LoanFrequency::Monthly,
            'first_due_date' => CarbonImmutable::now()->subMonths(2),
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
        ]);

        $installments = $generator->execute($loan, 2);

        // First 2 installments should be paid_before_tracking
        expect($installments[0]->status)->toBe(PlannedStatus::Paid);
        expect($installments[0]->paid_before_tracking)->toBeTrue();
        expect($installments[1]->status)->toBe(PlannedStatus::Paid);
        expect($installments[1]->paid_before_tracking)->toBeTrue();

        // Installment 3 & 4 remain planned
        expect($installments[2]->status)->toBe(PlannedStatus::Planned);
        expect($installments[3]->status)->toBe(PlannedStatus::Planned);

        // Make partial payment on installment 3 (500rb out of 1jt)
        Transaction::create([
            'household_id' => $this->household->id,
            'type' => TransactionType::Expense,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'planned_item_id' => $installments[2]->id,
            'amount' => 500000,
            'occurred_on' => now(),
        ]);

        $recomputed = $recompute->execute($installments[2]);
        expect($recomputed->status)->toBe(PlannedStatus::Partial);
        expect($recomputed->paid_amount)->toBe(500000);
        expect($recomputed->remainingAmount())->toBe(500000);

        // Execute accelerated early settlement to pay off remainder (1.5jt)
        $settlementTx = $acceleratedSettlement->execute(
            $loan,
            1500000,
            $this->account,
            CarbonImmutable::now(),
            'Pelunasan langsung',
            $this->user
        );

        expect($settlementTx->amount)->toBe(1500000);

        $loan->refresh();
        expect($loan->status)->toBe(LoanStatus::PaidOff);
        expect($loan->paid_off_at)->not->toBeNull();

        // Remaining planned installment should now be cancelled
        $inst4 = PlannedItem::find($installments[3]->id);
        expect($inst4?->status)->toBe(PlannedStatus::Cancelled);
    });
});
