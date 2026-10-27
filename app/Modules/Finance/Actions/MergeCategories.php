<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Models\Budget;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class MergeCategories
{
    /**
     * Merges source category into target category.
     * Moves all transactions and budgets, then archives the source category.
     */
    public function execute(Category $source, Category $target): void
    {
        if ($source->id === $target->id) {
            throw new InvalidArgumentException('Cannot merge category into itself.');
        }

        if ($source->isSystem()) {
            throw new InvalidArgumentException('System categories cannot be merged or deleted.');
        }

        if ($source->type !== $target->type) {
            throw new InvalidArgumentException('Cannot merge categories with different types.');
        }

        DB::transaction(function () use ($source, $target): void {
            // Reassign transactions
            Transaction::query()
                ->where('category_id', $source->id)
                ->update(['category_id' => $target->id]);

            // Reassign budgets: if target already has budget for same period, sum them; else move
            $sourceBudgets = Budget::query()->where('category_id', $source->id)->get();
            foreach ($sourceBudgets as $sb) {
                $existing = Budget::query()
                    ->where('category_id', $target->id)
                    ->where('period', $sb->period)
                    ->first();

                if ($existing !== null) {
                    $existing->update(['amount' => $existing->amount + $sb->amount]);
                    $sb->delete();
                } else {
                    $sb->update(['category_id' => $target->id]);
                }
            }

            // If source has children, move children to target
            Category::query()
                ->where('parent_id', $source->id)
                ->update(['parent_id' => $target->id]);

            // Archive the source category
            $source->archive();
        });
    }
}
