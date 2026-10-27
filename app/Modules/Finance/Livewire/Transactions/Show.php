<?php

declare(strict_types=1);

namespace App\Modules\Finance\Livewire\Transactions;

use App\Core\Support\HouseholdContext;
use App\Modules\Finance\Events\TransactionDeleted;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Services\AccountBalanceService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class Show extends Component
{
    public string $id;

    public function mount(string $id): void
    {
        $this->id = $id;
    }

    public function deleteTransaction(HouseholdContext $context, AccountBalanceService $balanceService): void
    {
        $household = $context->current();
        $tx = Transaction::query()
            ->where('household_id', $household->id)
            ->findOrFail($this->id);

        $accId = $tx->account_id;
        $toAccId = $tx->to_account_id;
        $plannedId = $tx->planned_item_id;
        $catId = $tx->category_id;
        $txId = $tx->id;

        $tx->delete();

        $balanceService->bustCache($accId);
        if ($toAccId !== null) {
            $balanceService->bustCache($toAccId);
        }

        TransactionDeleted::dispatch($txId, $accId, $toAccId, $plannedId, $catId);

        session()->flash('message', 'Transaksi berhasil dihapus.');
        $this->redirect(route('finance.transactions.index'), navigate: true);
    }

    public function render(HouseholdContext $context): View
    {
        $household = $context->current();
        $transaction = Transaction::query()
            ->where('household_id', $household->id)
            ->with(['account', 'toAccount', 'category', 'plannedItem', 'creator', 'relatedTransaction'])
            ->findOrFail($this->id);

        return view('finance::transactions.show', [
            'tx' => $transaction,
        ])->layout('layouts.app', ['title' => 'Detail Transaksi']);
    }
}
