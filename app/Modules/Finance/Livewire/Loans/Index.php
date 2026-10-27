<?php

declare(strict_types=1);

namespace App\Modules\Finance\Livewire\Loans;

use App\Core\Support\HouseholdContext;
use App\Modules\Finance\Enums\LoanDirection;
use App\Modules\Finance\Enums\LoanStatus;
use App\Modules\Finance\Enums\PlannedSource;
use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Models\Loan;
use App\Modules\Finance\Models\PlannedItem;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class Index extends Component
{
    public string $tab = 'payable'; // 'payable', 'receivable', 'paid_off'

    public function selectTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function render(HouseholdContext $context): View
    {
        $household = $context->current();

        $query = Loan::query()->where('household_id', $household->id);

        if ($this->tab === 'paid_off') {
            $query->where('status', LoanStatus::PaidOff);
        } else {
            $direction = $this->tab === 'payable' ? LoanDirection::Payable : LoanDirection::Receivable;
            $query->where('status', LoanStatus::Active)
                ->where('direction', $direction);
        }

        $loans = $query->with(['account', 'category'])->get()->map(function (Loan $loan): array {
            $installments = PlannedItem::query()
                ->where('source_type', PlannedSource::Loan)
                ->where('source_id', $loan->id)
                ->orderBy('sequence')
                ->get();

            $totalCount = $loan->installment_count;
            $paidCount = $installments->filter(fn (PlannedItem $i) => $i->status === PlannedStatus::Paid || $i->paid_before_tracking)->count();

            $paidAmount = $installments->sum('paid_amount')
                + $installments->where('paid_before_tracking', true)->sum('amount');

            // BR-FIN-044: Sisa pinjaman
            $remainingAmount = max(0, $loan->total_payable - $paidAmount);

            $nextInstallment = $installments->first(
                fn (PlannedItem $i) => in_array($i->status, [PlannedStatus::Planned, PlannedStatus::Partial], true)
            );

            return [
                'loan' => $loan,
                'totalCount' => $totalCount,
                'paidCount' => $paidCount,
                'paidAmount' => $paidAmount,
                'remainingAmount' => $remainingAmount,
                'nextInstallment' => $nextInstallment,
                'totalDiff' => $loan->totalDifference(),
            ];
        });

        return view('finance::loans.index', [
            'loans' => $loans,
        ])->layout('layouts.app', ['title' => 'Pinjaman (Hutang & Piutang)']);
    }
}
