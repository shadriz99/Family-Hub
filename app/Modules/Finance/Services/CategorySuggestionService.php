<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Models\Household;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Transaction;

final class CategorySuggestionService
{
    /**
     * Suggests a category based on the payee and household's transaction history.
     */
    public function suggestCategory(Household $household, string $payee, TransactionType $type): ?Category
    {
        $cleanPayee = trim($payee);
        if ($cleanPayee === '') {
            return null;
        }

        /** @var Transaction|null $match */
        $match = Transaction::query()
            ->where('household_id', $household->id)
            ->where('type', $type)
            ->whereNotNull('category_id')
            ->whereRaw('LOWER(payee) = ?', [strtolower($cleanPayee)])
            ->latest('occurred_on')
            ->first();

        return $match?->category;
    }
}
