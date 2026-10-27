<?php

declare(strict_types=1);

namespace App\Modules\Finance\Listeners;

use App\Models\User;
use App\Modules\Finance\Actions\RecomputePlannedItem;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Events\TransactionDeleted;
use App\Modules\Finance\Events\TransactionRecorded;
use App\Modules\Finance\Events\TransactionUpdated;
use App\Modules\Finance\Models\Budget;
use App\Modules\Finance\Models\PlannedItem;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Notifications\PartnerTransactionNotification;
use App\Modules\Finance\Services\AccountBalanceService;
use App\Modules\Finance\Services\BudgetService;
use App\Modules\Finance\Services\FinanceReminderService;

final class HandleTransactionEvents
{
    public function __construct(
        private readonly AccountBalanceService $balanceService,
        private readonly RecomputePlannedItem $recomputePlannedItem,
        private readonly BudgetService $budgetService,
        private readonly FinanceReminderService $reminderService,
    ) {
    }

    public function onRecorded(TransactionRecorded $event): void
    {
        $tx = Transaction::query()->with(['category.parent'])->find($event->transactionId);
        if ($tx === null) {
            return;
        }

        // 1. Invalidate account caches
        $this->balanceService->bustCache($tx->account_id);
        if ($tx->to_account_id !== null) {
            $this->balanceService->bustCache($tx->to_account_id);
        }

        // 2. Recompute planned item if attached
        if ($tx->planned_item_id !== null) {
            $plannedItem = PlannedItem::query()->find($tx->planned_item_id);
            if ($plannedItem !== null) {
                $updatedItem = $this->recomputePlannedItem->execute($plannedItem);
                $this->reminderService->scheduleForItem($updatedItem);
            }
        }

        // 3. Check budget thresholds if expense
        if ($tx->type === TransactionType::Expense && $tx->category_id !== null && $tx->planned_item_id === null) {
            $this->checkBudgetForCategory($tx);
        }

        // 4. Partner notification (BR-FIN-051)
        $this->notifyPartner($tx);
    }

    public function onUpdated(TransactionUpdated $event): void
    {
        $tx = Transaction::query()->with(['category.parent'])->find($event->transactionId);
        if ($tx === null) {
            return;
        }

        $this->balanceService->bustCache($tx->account_id);
        if ($tx->to_account_id !== null) {
            $this->balanceService->bustCache($tx->to_account_id);
        }
        if ($event->oldAccountId !== null) {
            $this->balanceService->bustCache($event->oldAccountId);
        }
        if ($event->oldToAccountId !== null) {
            $this->balanceService->bustCache($event->oldToAccountId);
        }

        if ($tx->planned_item_id !== null) {
            $plannedItem = PlannedItem::query()->find($tx->planned_item_id);
            if ($plannedItem !== null) {
                $updatedItem = $this->recomputePlannedItem->execute($plannedItem);
                $this->reminderService->scheduleForItem($updatedItem);
            }
        }

        if ($tx->type === TransactionType::Expense && $tx->category_id !== null && $tx->planned_item_id === null) {
            $this->checkBudgetForCategory($tx);
        }
    }

    public function onDeleted(TransactionDeleted $event): void
    {
        $this->balanceService->bustCache($event->accountId);
        if ($event->toAccountId !== null) {
            $this->balanceService->bustCache($event->toAccountId);
        }

        if ($event->plannedItemId !== null) {
            $plannedItem = PlannedItem::query()->find($event->plannedItemId);
            if ($plannedItem !== null) {
                $updatedItem = $this->recomputePlannedItem->execute($plannedItem);
                $this->reminderService->scheduleForItem($updatedItem);
            }
        }
    }

    private function checkBudgetForCategory(Transaction $tx): void
    {
        $category = $tx->category;
        if ($category === null) {
            return;
        }

        $parent = $category->parent ?? $category;

        $budgets = Budget::query()
            ->where('household_id', $tx->household_id)
            ->where('category_id', $parent->id)
            ->with(['category', 'household'])
            ->get();

        foreach ($budgets as $budget) {
            $this->budgetService->checkThresholds($budget);
        }
    }

    private function notifyPartner(Transaction $tx): void
    {
        if ($tx->created_by === null) {
            return;
        }

        $creator = User::query()->find($tx->created_by);
        if ($creator === null) {
            return;
        }

        $household = $tx->household;
        $partnerMembers = $household->members()
            ->where('user_id', '!=', $creator->id)
            ->with('user')
            ->get();

        foreach ($partnerMembers as $member) {
            $partnerUser = $member->user;
            if ($partnerUser !== null && $partnerUser->notify_push) {
                $partnerUser->notify(new PartnerTransactionNotification($tx, $creator));
            }
        }
    }
}
