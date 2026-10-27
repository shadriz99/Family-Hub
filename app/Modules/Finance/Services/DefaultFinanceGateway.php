<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Support\HouseholdContext;
use App\Core\Support\PeriodResolver;
use App\Modules\Finance\Contracts\FinanceGateway;
use App\Modules\Finance\DTO\ExpenseData;
use App\Modules\Finance\DTO\PlannedExpenseData;
use App\Modules\Finance\Enums\AllocationCategory;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Enums\Direction;
use App\Modules\Finance\Enums\PlannedSource;
use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Events\TransactionDeleted;
use App\Modules\Finance\Events\TransactionRecorded;
use App\Modules\Finance\Events\TransactionUpdated;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\PlannedItem;
use App\Modules\Finance\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class DefaultFinanceGateway implements FinanceGateway
{
    public function __construct(
        private readonly HouseholdContext $context,
        private readonly AccountBalanceService $balanceService,
    ) {
    }

    public function recordExpense(ExpenseData $data): string
    {
        $household = $this->context->current();

        return DB::transaction(function () use ($data, $household): string {
            $category = Category::query()->findOrFail($data->categoryId);

            $allocation = $data->allocation !== null
                ? AllocationCategory::tryFrom($data->allocation)
                : $category->default_allocation;

            if ($allocation === null) {
                throw new InvalidArgumentException("Expense category '{$category->name}' must have a valid allocation.");
            }

            $tx = new Transaction();
            $tx->household_id = $household->id;
            $tx->type = TransactionType::Expense;
            $tx->account_id = $data->accountId;
            $tx->category_id = $category->id;
            $tx->amount = $data->amount;
            $tx->occurred_on = $data->occurredOn;
            $tx->note = $data->note;
            $tx->source_type = $data->sourceType;
            $tx->source_id = $data->sourceId;
            $tx->planned_item_id = $data->plannedItemId;
            $tx->allocation = $allocation;
            $tx->save();

            $this->balanceService->bustCache($data->accountId);
            TransactionRecorded::dispatch($tx->id);

            return $tx->id;
        });
    }

    public function updateExpense(string $transactionId, ExpenseData $data): void
    {
        DB::transaction(function () use ($transactionId, $data): void {
            /** @var Transaction $tx */
            $tx = Transaction::query()->findOrFail($transactionId);
            $oldAccountId = $tx->account_id;

            $category = Category::query()->findOrFail($data->categoryId);
            $allocation = $data->allocation !== null
                ? AllocationCategory::tryFrom($data->allocation)
                : $category->default_allocation;

            $tx->account_id = $data->accountId;
            $tx->category_id = $category->id;
            $tx->amount = $data->amount;
            $tx->occurred_on = $data->occurredOn;
            $tx->note = $data->note;
            $tx->source_type = $data->sourceType;
            $tx->source_id = $data->sourceId;
            $tx->planned_item_id = $data->plannedItemId;
            $tx->allocation = $allocation;
            $tx->save();

            $this->balanceService->bustCache($oldAccountId, $data->accountId);
            TransactionUpdated::dispatch($tx->id, $oldAccountId);
        });
    }

    public function deleteExpense(string $transactionId): void
    {
        DB::transaction(function () use ($transactionId): void {
            /** @var Transaction $tx */
            $tx = Transaction::query()->findOrFail($transactionId);
            $accountId = $tx->account_id;
            $toAccountId = $tx->to_account_id;
            $plannedItemId = $tx->planned_item_id;
            $categoryId = $tx->category_id;

            $tx->delete();

            $this->balanceService->bustCache($accountId);
            TransactionDeleted::dispatch($transactionId, $accountId, $toAccountId, $plannedItemId, $categoryId);
        });
    }

    public function upsertPlannedExpense(PlannedExpenseData $data): string
    {
        $household = $this->context->current();
        $resolver = PeriodResolver::for($household);
        $period = $resolver->forDate($data->dueDate)->key;

        return DB::transaction(function () use ($data, $household, $period): string {
            $source = PlannedSource::tryFrom($data->sourceType);

            $item = PlannedItem::query()
                ->where('source_type', $source ?? $data->sourceType)
                ->where('source_id', $data->sourceId)
                ->whereDate('due_date', $data->dueDate->toDateString())
                ->first();

            if ($item === null) {
                $item = new PlannedItem();
                $item->household_id = $household->id;
                $item->source_type = $source ?? PlannedSource::Manual;
                $item->source_id = $data->sourceId;
                $item->due_date = $data->dueDate;
                $item->status = PlannedStatus::Planned;
            }

            $item->direction = Direction::Expense;
            $item->name = $data->name;
            $item->category_id = $data->categoryId;
            $item->account_id = $data->accountId;
            $item->amount = $data->amount;
            $item->period = $period;
            $item->save();

            return $item->id;
        });
    }

    public function cancelPlannedExpenses(string $sourceType, string $sourceId, ?CarbonImmutable $fromDate = null): void
    {
        $source = PlannedSource::tryFrom($sourceType);

        $query = PlannedItem::query()
            ->where(function ($q) use ($sourceType, $source) {
                if ($source !== null) {
                    $q->where('source_type', $source);
                } else {
                    $q->where('source_type', $sourceType);
                }
            })
            ->where('source_id', $sourceId)
            ->where('status', PlannedStatus::Planned);

        if ($fromDate !== null) {
            $query->where('due_date', '>=', $fromDate->toDateString());
        }

        $query->update([
            'status' => PlannedStatus::Cancelled,
            'status_reason' => __('finance.cancelled_by_source'),
        ]);
    }

    public function accountOptions(): array
    {
        $household = $this->context->current();

        return Account::query()
            ->where('household_id', $household->id)
            ->whereNull('archived_at')
            ->orderBy('sort_order')
            ->pluck('name', 'id')
            ->all();
    }

    public function expenseCategoryOptions(): array
    {
        $household = $this->context->current();

        return Category::query()
            ->where('household_id', $household->id)
            ->where('type', CategoryType::Expense)
            ->whereNull('archived_at')
            ->orderBy('sort_order')
            ->pluck('name', 'id')
            ->all();
    }

    public function defaultCategoryId(string $systemKey): ?string
    {
        $household = $this->context->current();

        return Category::query()
            ->where('household_id', $household->id)
            ->where('system_key', $systemKey)
            ->value('id');
    }
}
