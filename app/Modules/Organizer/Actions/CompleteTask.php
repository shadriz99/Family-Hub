<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Actions;

use App\Core\Models\HouseholdMember;
use App\Core\Recurrence\Recurrence;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Finance\Contracts\FinanceGateway;
use App\Modules\Finance\DTO\ExpenseData;
use App\Modules\Organizer\Enums\TaskStatus;
use App\Modules\Organizer\Models\Task;
use App\Modules\Organizer\Models\TaskItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class CompleteTask
{
    public function __construct(
        private readonly HouseholdContext $context,
        private readonly FinanceGateway $financeGateway,
    ) {
    }

    public function handle(Task $task, User $user, ?ExpenseData $expenseData = null): Task
    {
        return DB::transaction(function () use ($task, $user, $expenseData): Task {
            $task->status = TaskStatus::Done;
            $task->completed_at = CarbonImmutable::now();
            $task->completed_by = $user->id;

            // If expense data is provided and expense recording is flagged
            if ($expenseData !== null) {
                $transactionId = $this->financeGateway->recordExpense($expenseData);
                $task->expense_transaction_id = $transactionId;
            }

            $task->save();

            // Handle recurring task instance generation (BR-ORG-001, BR-ORG-002, BR-ORG-004)
            if (! empty($task->rrule) && ! empty($task->series_id)) {
                $this->generateNextRecurringInstance($task);
            }

            return $task;
        });
    }

    private function generateNextRecurringInstance(Task $task): ?Task
    {
        // BR-ORG-001: Only one open instance per series_id
        $hasOpen = Task::query()
            ->where('household_id', $task->household_id)
            ->where('series_id', $task->series_id)
            ->where('status', TaskStatus::Open)
            ->exists();

        if ($hasOpen) {
            return null;
        }

        $currentDueDate = $task->due_date ?? CarbonImmutable::now();
        $nextOccurrence = Recurrence::next($task->rrule, $currentDueDate, $currentDueDate);

        if ($nextOccurrence === null) {
            // Series ended
            return null;
        }

        // BR-ORG-004: Rotasi penanggung jawab
        $nextAssigneeId = $task->assignee_user_id;
        if ($task->rotate_assignee) {
            $household = $this->context->current();
            $members = HouseholdMember::query()
                ->where('household_id', $household->id)
                ->pluck('user_id')
                ->values();

            if ($members->count() > 1) {
                $otherMember = $members->first(fn (string $id): bool => $id !== $task->assignee_user_id);
                if ($otherMember !== null) {
                    $nextAssigneeId = $otherMember;
                }
            }
        }

        $nextTask = new Task();
        $nextTask->household_id = $task->household_id;
        $nextTask->created_by = $task->created_by;
        $nextTask->series_id = $task->series_id;
        $nextTask->title = $task->title;
        $nextTask->description = $task->description;
        $nextTask->assignee_user_id = $nextAssigneeId;
        $nextTask->due_date = $nextOccurrence;
        $nextTask->due_time = $task->due_time;
        $nextTask->priority = $task->priority;
        $nextTask->rrule = $task->rrule;
        $nextTask->rotate_assignee = $task->rotate_assignee;
        $nextTask->reminder_offset = $task->reminder_offset;
        $nextTask->status = TaskStatus::Open;
        $nextTask->expense_on_complete = $task->expense_on_complete;
        $nextTask->expense_estimate = $task->expense_estimate;
        $nextTask->expense_category_id = $task->expense_category_id;
        $nextTask->save();

        // Reset and clone checklist items
        foreach ($task->items as $item) {
            $nextItem = new TaskItem();
            $nextItem->task_id = $nextTask->id;
            $nextItem->text = $item->text;
            $nextItem->is_done = false;
            $nextItem->sort_order = $item->sort_order;
            $nextItem->save();
        }

        return $nextTask;
    }
}
