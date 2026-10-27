<?php

declare(strict_types=1);

namespace App\Modules\Finance\Livewire\Loans;

use App\Core\Support\HouseholdContext;
use App\Core\Support\Money;
use App\Modules\Finance\Actions\AcceleratedLoanSettlement;
use App\Modules\Finance\Actions\RecomputePlannedItem;
use App\Modules\Finance\Enums\Direction;
use App\Modules\Finance\Enums\LoanStatus;
use App\Modules\Finance\Enums\PlannedSource;
use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Events\TransactionRecorded;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Loan;
use App\Modules\Finance\Models\PlannedItem;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Services\AccountBalanceService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

final class Show extends Component
{
    public string $id;

    // Installment Pay Modal
    public bool $showPayModal = false;
    public ?string $payItemId = null;
    public string $payItemName = '';
    public string $payAmount = '';
    public ?string $payAccountId = null;
    public string $payDate = '';
    public string $payNote = '';

    // Accelerated Settlement Modal
    public bool $showAcceleratedModal = false;
    public string $settlementAmount = '';
    public ?string $settlementAccountId = null;
    public string $settlementDate = '';
    public string $settlementNote = '';

    public function mount(string $id, HouseholdContext $context): void
    {
        $this->id = $id;
        $this->payDate = now()->toDateString();
        $this->settlementDate = now()->toDateString();
    }

    public function openPay(string $itemId): void
    {
        $item = PlannedItem::query()->findOrFail($itemId);
        $this->payItemId = $item->id;
        $this->payItemName = $item->name;
        $this->payAmount = (string) $item->remainingAmount();
        $this->payAccountId = $item->account_id ?? Account::query()->where('household_id', $item->household_id)->first()?->id;
        $this->payDate = now()->toDateString();
        $this->payNote = '';
        $this->showPayModal = true;
    }

    public function executePayment(RecomputePlannedItem $recomputeAction, AccountBalanceService $balanceService): void
    {
        $this->validate([
            'payAmount' => ['required'],
            'payAccountId' => ['required', 'exists:fin_accounts,id'],
            'payDate' => ['required', 'date'],
        ]);

        $item = PlannedItem::query()->findOrFail($this->payItemId);
        $parsedAmount = Money::parse($this->payAmount);
        $date = CarbonImmutable::parse($this->payDate);

        DB::transaction(function () use ($item, $parsedAmount, $date, $recomputeAction, $balanceService): void {
            $type = $item->direction === Direction::Expense ? TransactionType::Expense : TransactionType::Income;

            $tx = new Transaction();
            $tx->household_id = $item->household_id;
            $tx->type = $type;
            $tx->account_id = $this->payAccountId;
            $tx->category_id = $item->category_id;
            $tx->planned_item_id = $item->id;
            $tx->amount = $parsedAmount;
            $tx->occurred_on = $date;
            $tx->note = $this->payNote !== '' ? $this->payNote : "Pembayaran {$item->name}";
            $tx->created_by = auth()->id();
            $tx->save();

            $balanceService->bustCache($this->payAccountId);
            TransactionRecorded::dispatch($tx->id);

            $recomputeAction->execute($item);
        });

        $this->showPayModal = false;
        session()->flash('message', "Pembayaran untuk '{$item->name}' berhasil dicatat.");
    }

    public function openAcceleratedModal(HouseholdContext $context): void
    {
        $household = $context->current();
        $loan = Loan::query()->where('household_id', $household->id)->findOrFail($this->id);

        $installments = PlannedItem::query()
            ->where('source_type', PlannedSource::Loan)
            ->where('source_id', $loan->id)
            ->get();

        $paidAmount = $installments->sum('paid_amount')
            + $installments->where('paid_before_tracking', true)->sum('amount');

        $remaining = max(0, $loan->total_payable - $paidAmount);

        $this->settlementAmount = (string) $remaining;
        $this->settlementAccountId = $loan->account_id;
        $this->settlementDate = now()->toDateString();
        $this->settlementNote = '';
        $this->showAcceleratedModal = true;
    }

    public function executeAcceleratedSettlement(
        AcceleratedLoanSettlement $action,
        HouseholdContext $context
    ): void {
        $household = $context->current();
        $loan = Loan::query()->where('household_id', $household->id)->findOrFail($this->id);

        $this->validate([
            'settlementAmount' => ['required'],
            'settlementAccountId' => ['required', 'exists:fin_accounts,id'],
            'settlementDate' => ['required', 'date'],
        ]);

        $account = Account::query()->findOrFail($this->settlementAccountId);
        $amount = Money::parse($this->settlementAmount);
        $date = CarbonImmutable::parse($this->settlementDate);

        $action->execute(
            $loan,
            $amount,
            $account,
            $date,
            $this->settlementNote !== '' ? $this->settlementNote : null,
            auth()->user()
        );

        $this->showAcceleratedModal = false;
        session()->flash('message', "Pelunasan dipercepat untuk '{$loan->name}' berhasil diproses.");
    }

    public function render(HouseholdContext $context): View
    {
        $household = $context->current();
        $loan = Loan::query()
            ->where('household_id', $household->id)
            ->with(['account', 'category'])
            ->findOrFail($this->id);

        $installments = PlannedItem::query()
            ->where('source_type', PlannedSource::Loan)
            ->where('source_id', $loan->id)
            ->orderBy('sequence')
            ->get();

        $totalCount = $loan->installment_count;
        $paidCount = $installments->filter(fn (PlannedItem $i) => $i->status === PlannedStatus::Paid || $i->paid_before_tracking)->count();

        $paidAmount = $installments->sum('paid_amount')
            + $installments->where('paid_before_tracking', true)->sum('amount');

        $remainingAmount = max(0, $loan->total_payable - $paidAmount);

        $lastInstallment = $installments->last();
        $estimatedSettlementDate = $loan->status === LoanStatus::PaidOff
            ? $loan->paid_off_at
            : $lastInstallment?->due_date;

        $accounts = Account::query()
            ->where('household_id', $household->id)
            ->whereNull('archived_at')
            ->orderBy('sort_order')
            ->get();

        return view('finance::loans.show', [
            'loan' => $loan,
            'installments' => $installments,
            'totalCount' => $totalCount,
            'paidCount' => $paidCount,
            'paidAmount' => $paidAmount,
            'remainingAmount' => $remainingAmount,
            'estimatedSettlementDate' => $estimatedSettlementDate,
            'accounts' => $accounts,
        ])->layout('layouts.app', ['title' => "Detail Pinjaman: {$loan->name}"]);
    }
}
