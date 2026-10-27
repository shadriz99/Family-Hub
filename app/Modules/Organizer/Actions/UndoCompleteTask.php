<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Actions;

use App\Modules\Finance\Contracts\FinanceGateway;
use App\Modules\Organizer\Enums\TaskStatus;
use App\Modules\Organizer\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class UndoCompleteTask
{
    public function __construct(
        private readonly FinanceGateway $financeGateway,
    ) {
    }

    public function handle(Task $task): Task
    {
        return DB::transaction(function () use ($task): Task {
            $completedAt = $task->completed_at ? CarbonImmutable::instance($task->completed_at) : null;
            $isWithin5Seconds = $completedAt !== null && $completedAt->diffInSeconds(now()) <= 5;

            // BR-ORG-003: If within 5 seconds and recurring, delete untouched next instance
            if ($isWithin5Seconds && ! empty($task->series_id)) {
                $nextInstance = Task::query()
                    ->where('household_id', $task->household_id)
                    ->where('series_id', $task->series_id)
                    ->where('id', '!=', $task->id)
                    ->where('status', TaskStatus::Open)
                    ->latest()
                    ->first();

                if ($nextInstance !== null) {
                    $hasModifiedItems = $nextInstance->items()->where('is_done', true)->exists();
                    if (! $hasModifiedItems) {
                        $nextInstance->forceDelete();
                    }
                }
            }

            // If expense transaction was recorded, delete it
            if ($task->expense_transaction_id !== null) {
                $this->financeGateway->deleteExpense($task->expense_transaction_id);
                $task->expense_transaction_id = null;
            }

            $task->status = TaskStatus::Open;
            $task->completed_at = null;
            $task->completed_by = null;
            $task->save();

            return $task;
        });
    }
}
