<?php

declare(strict_types=1);

namespace App\Modules\Finance\Livewire\Loans;

use App\Core\Support\HouseholdContext;
use App\Core\Support\Money;
use App\Modules\Finance\Actions\GenerateLoanInstallments;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Enums\LoanDirection;
use App\Modules\Finance\Enums\LoanFrequency;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Events\TransactionRecorded;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Loan;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Services\AccountBalanceService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

final class Form extends Component
{
    public ?string $loanId = null;

    public string $direction = 'payable';
    public string $name = '';
    public string $counterparty = '';
    public string $principal = '';
    public int $installment_count = 12;
    public string $installment_amount = '';
    public ?string $total_payable = null;
    public string $frequency = 'monthly';
    public string $first_due_date = '';
    public ?string $account_id = null;
    public ?string $category_id = null;
    public int $paid_before_count = 0;
    public bool $record_disbursement = false;
    public ?string $interest_note = null;
    public int $remind_days_before = 3;
    public ?string $notes = null;

    public function mount(HouseholdContext $context, ?string $id = null): void
    {
        $household = $context->current();
        $this->first_due_date = now()->toDateString();

        $defaultAccount = Account::query()
            ->where('household_id', $household->id)
            ->whereNull('archived_at')
            ->orderBy('sort_order')
            ->first();
        $this->account_id = $defaultAccount?->id;

        $defaultCategory = Category::query()
            ->where('household_id', $household->id)
            ->where('system_key', 'loan_installment')
            ->first();
        $this->category_id = $defaultCategory?->id;

        if ($id !== null) {
            $loan = Loan::query()->where('household_id', $household->id)->findOrFail($id);
            $this->loanId = $loan->id;
            $this->direction = $loan->direction->value;
            $this->name = $loan->name;
            $this->counterparty = $loan->counterparty;
            $this->principal = (string) $loan->principal;
            $this->installment_count = $loan->installment_count;
            $this->installment_amount = (string) $loan->installment_amount;
            $this->total_payable = (string) $loan->total_payable;
            $this->frequency = $loan->frequency->value;
            $this->first_due_date = $loan->first_due_date->toDateString();
            $this->account_id = $loan->account_id;
            $this->category_id = $loan->category_id;
            $this->interest_note = $loan->interest_note;
            $this->remind_days_before = $loan->remind_days_before;
            $this->notes = $loan->notes;
        }
    }

    public function updatedDirection(): void
    {
        $systemKey = $this->direction === 'payable' ? 'loan_installment' : 'loan_receipt';
        $category = Category::query()
            ->where('household_id', currentHousehold()->id)
            ->where('system_key', $systemKey)
            ->first();

        if ($category !== null) {
            $this->category_id = $category->id;
        }
    }

    public function updatedInstallmentAmount(): void
    {
        try {
            $amount = Money::parse($this->installment_amount);
            $this->total_payable = (string) ($amount * max(1, $this->installment_count));
        } catch (\Throwable) {
            // ignore
        }
    }

    public function updatedInstallmentCount(): void
    {
        try {
            $amount = Money::parse($this->installment_amount);
            $this->total_payable = (string) ($amount * max(1, $this->installment_count));
        } catch (\Throwable) {
            // ignore
        }
    }

    public function saveLoan(
        HouseholdContext $context,
        GenerateLoanInstallments $generator,
        AccountBalanceService $balanceService
    ): void {
        $household = $context->current();

        $this->validate([
            'direction' => ['required', Rule::enum(LoanDirection::class)],
            'name' => ['required', 'string', 'max:80'],
            'counterparty' => ['required', 'string', 'max:100'],
            'principal' => ['required'],
            'installment_count' => ['required', 'integer', 'min:1', 'max:600'],
            'installment_amount' => ['required'],
            'first_due_date' => ['required', 'date'],
            'account_id' => ['required', 'exists:fin_accounts,id'],
            'category_id' => ['required', 'exists:fin_categories,id'],
            'paid_before_count' => ['integer', 'min:0', "max:{$this->installment_count}"],
        ]);

        $principalAmount = Money::parse($this->principal);
        $instAmount = Money::parse($this->installment_amount);
        $totalPay = $this->total_payable !== null && trim($this->total_payable) !== ''
            ? Money::parse($this->total_payable)
            : ($instAmount * $this->installment_count);

        if ($totalPay < ($instAmount * ($this->installment_count - 1)) + 1) {
            $this->addError('total_payable', 'Total bayar terlalu kecil untuk jumlah cicilan.');

            return;
        }

        $loan = DB::transaction(function () use ($household, $principalAmount, $instAmount, $totalPay, $generator, $balanceService): Loan {
            $loan = $this->loanId !== null
                ? Loan::query()->where('household_id', $household->id)->findOrFail($this->loanId)
                : new Loan();

            $loan->household_id = $household->id;
            $loan->direction = LoanDirection::from($this->direction);
            $loan->name = $this->name;
            $loan->counterparty = $this->counterparty;
            $loan->principal = $principalAmount;
            $loan->installment_count = $this->installment_count;
            $loan->installment_amount = $instAmount;
            $loan->total_payable = $totalPay;
            $loan->frequency = LoanFrequency::from($this->frequency);
            $loan->first_due_date = CarbonImmutable::parse($this->first_due_date);
            $loan->account_id = $this->account_id;
            $loan->category_id = $this->category_id;
            $loan->interest_note = $this->interest_note;
            $loan->remind_days_before = $this->remind_days_before;
            $loan->notes = $this->notes;
            if ($loan->created_by === null) {
                $loan->created_by = auth()->id();
            }
            $loan->save();

            // Record disbursement transaction if requested (US-FIN-06 AC1)
            if ($this->record_disbursement && $this->loanId === null) {
                $isPayable = $loan->direction === LoanDirection::Payable;
                $txType = $isPayable ? TransactionType::Income : TransactionType::Expense;
                $systemKey = $isPayable ? 'loan_disbursement_in' : 'loan_disbursement_out';

                $disbCategory = Category::query()
                    ->where('household_id', $household->id)
                    ->where('system_key', $systemKey)
                    ->first();

                if ($disbCategory !== null) {
                    $tx = new Transaction();
                    $tx->household_id = $household->id;
                    $tx->type = $txType;
                    $tx->account_id = $loan->account_id;
                    $tx->category_id = $disbCategory->id;
                    $tx->amount = $loan->principal;
                    $tx->occurred_on = $loan->first_due_date;
                    $tx->payee = $loan->counterparty;
                    $tx->note = "Pencairan Pinjaman {$loan->name}";
                    $tx->created_by = auth()->id();
                    $tx->save();

                    $loan->disbursement_transaction_id = $tx->id;
                    $loan->save();

                    $balanceService->bustCache($loan->account_id);
                    TransactionRecorded::dispatch($tx->id);
                }
            }

            // Generate PlannedItems
            $generator->execute($loan, $this->paid_before_count);

            return $loan;
        });

        session()->flash('message', "Pinjaman '{$loan->name}' berhasil disimpan.");
        $this->redirect(route('finance.loans.show', $loan->id), navigate: true);
    }

    public function render(HouseholdContext $context): View
    {
        $household = $context->current();

        $accounts = Account::query()
            ->where('household_id', $household->id)
            ->whereNull('archived_at')
            ->orderBy('sort_order')
            ->get();

        $catType = $this->direction === 'payable' ? CategoryType::Expense : CategoryType::Income;
        $categories = Category::query()
            ->where('household_id', $household->id)
            ->where('type', $catType)
            ->whereNull('archived_at')
            ->orderBy('sort_order')
            ->get();

        // Calculate schedule preview (AC2)
        $schedulePreview = [];
        try {
            $parsedInst = Money::parse($this->installment_amount);
            $parsedTotal = $this->total_payable !== null && trim($this->total_payable) !== ''
                ? Money::parse($this->total_payable)
                : ($parsedInst * $this->installment_count);

            $n = $this->installment_count;
            if ($n >= 1 && $parsedInst > 0 && $this->first_due_date !== '') {
                $lastInst = $parsedTotal - ($parsedInst * ($n - 1));
                $firstDate = CarbonImmutable::parse($this->first_due_date);
                $targetDay = $firstDate->day;

                for ($k = 1; $k <= min(12, $n); $k++) {
                    $amount = ($k === $n) ? $lastInst : $parsedInst;
                    $date = $k === 1
                        ? $firstDate
                        : $firstDate->startOfMonth()->addMonthsNoOverflow($k - 1)->day(min($targetDay, $firstDate->startOfMonth()->addMonthsNoOverflow($k - 1)->daysInMonth));

                    $schedulePreview[] = [
                        'sequence' => $k,
                        'due_date' => $date,
                        'amount' => $amount,
                        'paid_before' => ($k <= $this->paid_before_count),
                    ];
                }
            }
        } catch (\Throwable) {
            // ignore preview calculation error
        }

        return view('finance::loans.form', [
            'accounts' => $accounts,
            'categories' => $categories,
            'schedulePreview' => $schedulePreview,
        ])->layout('layouts.app', ['title' => 'Form Pinjaman']);
    }
}
