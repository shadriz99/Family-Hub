<?php

declare(strict_types=1);

namespace App\Modules\Finance\Contracts;

use App\Modules\Finance\DTO\ExpenseData;
use App\Modules\Finance\DTO\PlannedExpenseData;
use Carbon\CarbonImmutable;

interface FinanceGateway
{
    /**
     * Return transaction ID, or null when Finance integration is disabled.
     */
    public function recordExpense(ExpenseData $data): ?string;

    public function updateExpense(string $transactionId, ExpenseData $data): void;

    public function deleteExpense(string $transactionId): void;

    /**
     * Create or update planned expense (idempotent by sourceType, sourceId, dueDate).
     * Return PlannedItem ID, or null when Finance integration is disabled.
     */
    public function upsertPlannedExpense(PlannedExpenseData $data): ?string;

    public function cancelPlannedExpenses(string $sourceType, string $sourceId, ?CarbonImmutable $fromDate = null): void;

    /**
     * @return array<string, string>
     */
    public function accountOptions(): array;

    /**
     * @return array<string, string>
     */
    public function expenseCategoryOptions(): array;

    public function defaultCategoryId(string $systemKey): ?string;
}
