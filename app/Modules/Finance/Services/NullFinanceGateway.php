<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Contracts\FinanceGateway;
use App\Modules\Finance\DTO\ExpenseData;
use App\Modules\Finance\DTO\PlannedExpenseData;
use Carbon\CarbonImmutable;

final class NullFinanceGateway implements FinanceGateway
{
    public function recordExpense(ExpenseData $data): ?string
    {
        return null;
    }

    public function updateExpense(string $transactionId, ExpenseData $data): void
    {
    }

    public function deleteExpense(string $transactionId): void
    {
    }

    public function upsertPlannedExpense(PlannedExpenseData $data): ?string
    {
        return null;
    }

    public function cancelPlannedExpenses(string $sourceType, string $sourceId, ?CarbonImmutable $fromDate = null): void
    {
    }

    public function accountOptions(): array
    {
        return [];
    }

    public function expenseCategoryOptions(): array
    {
        return [];
    }

    public function defaultCategoryId(string $systemKey): ?string
    {
        return null;
    }
}
