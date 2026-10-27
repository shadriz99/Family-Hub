<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Livewire\Tasks;

use App\Core\Models\HouseholdMember;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Finance\Contracts\FinanceGateway;
use App\Modules\Finance\DTO\ExpenseData;
use App\Modules\Organizer\Actions\CompleteTask;
use App\Modules\Organizer\Actions\CreateTask;
use App\Modules\Organizer\Actions\UndoCompleteTask;
use App\Modules\Organizer\Enums\TaskPriority;
use App\Modules\Organizer\Enums\TaskStatus;
use App\Modules\Organizer\Models\Task;
use App\Modules\Organizer\Models\TaskItem;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;

final class Index extends Component
{
    #[Url]
    public string $tab = 'today'; // today, upcoming, no_date, completed

    #[Url]
    public string $assigneeFilter = 'all'; // all, me, partner

    // Quick add fields
    public string $quickTitle = '';
    public string $quickChipDate = 'today';
    public string $quickChipAssignee = 'me';

    // Undo toast state
    public ?string $lastCompletedTaskId = null;
    public ?string $undoMessage = null;

    // Expense bottom sheet on complete
    public bool $showExpenseModal = false;
    public ?string $completingTaskId = null;
    public ?string $expenseAccountId = null;
    public ?string $expenseCategoryId = null;
    public int $expenseAmount = 0;

    public function mount(): void
    {
        // Set default account if Finance is available
        $gateway = app(FinanceGateway::class);
        $accounts = $gateway->accountOptions();
        if (! empty($accounts)) {
            $this->expenseAccountId = array_key_first($accounts);
        }
    }

    public function toggleComplete(string $taskId): void
    {
        /** @var User $user */
        $user = auth()->user();
        /** @var Task $task */
        $task = Task::query()->findOrFail($taskId);

        if ($task->status === TaskStatus::Done) {
            // Reopen task
            app(UndoCompleteTask::class)->handle($task);
            $this->undoMessage = null;
            $this->lastCompletedTaskId = null;

            return;
        }

        // If task has expense_on_complete and Finance is enabled, prompt modal
        if ($task->expense_on_complete && config('modules.finance', false)) {
            $this->completingTaskId = $task->id;
            $this->expenseAmount = $task->expense_estimate ?? 0;
            $this->expenseCategoryId = $task->expense_category_id;
            $this->showExpenseModal = true;

            return;
        }

        // Complete normally
        app(CompleteTask::class)->handle($task, $user);
        $this->lastCompletedTaskId = $task->id;
        $this->undoMessage = "Tugas '{$task->title}' selesai.";
    }

    public function confirmExpenseComplete(): void
    {
        if ($this->completingTaskId === null) {
            return;
        }

        /** @var User $user */
        $user = auth()->user();
        /** @var Task $task */
        $task = Task::query()->findOrFail($this->completingTaskId);

        $expenseData = null;
        if ($this->expenseAccountId && $this->expenseCategoryId && $this->expenseAmount > 0) {
            $expenseData = new ExpenseData(
                accountId: $this->expenseAccountId,
                categoryId: $this->expenseCategoryId,
                amount: $this->expenseAmount,
                occurredOn: CarbonImmutable::now(),
                note: "Pengeluaran tugas: {$task->title}",
                sourceType: 'org_task',
                sourceId: $task->id,
            );
        }

        app(CompleteTask::class)->handle($task, $user, $expenseData);

        $this->lastCompletedTaskId = $task->id;
        $this->undoMessage = "Tugas '{$task->title}' selesai dicatat.";
        $this->showExpenseModal = false;
        $this->completingTaskId = null;
    }

    public function skipExpenseComplete(): void
    {
        if ($this->completingTaskId === null) {
            return;
        }

        /** @var User $user */
        $user = auth()->user();
        /** @var Task $task */
        $task = Task::query()->findOrFail($this->completingTaskId);

        app(CompleteTask::class)->handle($task, $user);

        $this->lastCompletedTaskId = $task->id;
        $this->undoMessage = "Tugas '{$task->title}' selesai tanpa pengeluaran.";
        $this->showExpenseModal = false;
        $this->completingTaskId = null;
    }

    public function undoComplete(): void
    {
        if ($this->lastCompletedTaskId === null) {
            return;
        }

        $task = Task::query()->find($this->lastCompletedTaskId);
        if ($task !== null) {
            app(UndoCompleteTask::class)->handle($task);
        }

        $this->lastCompletedTaskId = null;
        $this->undoMessage = null;
    }

    public function toggleItem(string $itemId): void
    {
        $item = TaskItem::query()->findOrFail($itemId);
        $item->is_done = ! $item->is_done;
        $item->save();
    }

    public function quickAdd(): void
    {
        $this->validate([
            'quickTitle' => ['required', 'string', 'max:150'],
        ]);

        /** @var User $user */
        $user = auth()->user();
        $household = app(HouseholdContext::class)->current();

        $dueDate = match ($this->quickChipDate) {
            'today' => CarbonImmutable::now()->toDateString(),
            'tomorrow' => CarbonImmutable::now()->addDay()->toDateString(),
            'weekend' => CarbonImmutable::now()->next(CarbonImmutable::SATURDAY)->toDateString(),
            default => null,
        };

        $assigneeId = null;
        if ($this->quickChipAssignee === 'me') {
            $assigneeId = $user->id;
        } elseif ($this->quickChipAssignee === 'partner') {
            $partner = HouseholdMember::query()
                ->where('household_id', $household->id)
                ->where('user_id', '!=', $user->id)
                ->first();
            $assigneeId = $partner?->user_id;
        }

        app(CreateTask::class)->handle($user, [
            'title' => $this->quickTitle,
            'due_date' => $dueDate,
            'assignee_user_id' => $assigneeId,
            'priority' => TaskPriority::Normal,
        ]);

        $this->quickTitle = '';
    }

    public function render(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $household = app(HouseholdContext::class)->current();
        $todayStr = CarbonImmutable::now()->toDateString();

        $query = Task::query()
            ->with(['items', 'assignee', 'completedByUser'])
            ->where('household_id', $household->id);

        // Apply assignee filter
        if ($this->assigneeFilter === 'me') {
            $query->where('assignee_user_id', $user->id);
        } elseif ($this->assigneeFilter === 'partner') {
            $query->where(function (Builder $q) use ($user): void {
                $q->whereNotNull('assignee_user_id')
                    ->where('assignee_user_id', '!=', $user->id);
            });
        }

        // Apply tab query
        match ($this->tab) {
            'today' => $query->where('status', TaskStatus::Open)
                ->whereNotNull('due_date')
                ->where('due_date', '<=', $todayStr)
                ->orderBy('due_date')
                ->orderBy('due_time'),
            'upcoming' => $query->where('status', TaskStatus::Open)
                ->whereNotNull('due_date')
                ->where('due_date', '>', $todayStr)
                ->orderBy('due_date')
                ->orderBy('due_time'),
            'no_date' => $query->where('status', TaskStatus::Open)
                ->whereNull('due_date')
                ->orderBy('created_at', 'desc'),
            'completed' => $query->where('status', TaskStatus::Done)
                ->where('completed_at', '>=', CarbonImmutable::now()->subDays(30))
                ->orderBy('completed_at', 'desc'),
            default => null,
        };

        $tasks = $query->get();

        // Gateway accounts and categories for expense modal
        $gateway = app(FinanceGateway::class);
        $accounts = $gateway->accountOptions();
        $categories = $gateway->expenseCategoryOptions();

        return view('modules.organizer.tasks.index', [
            'tasks' => $tasks,
            'accounts' => $accounts,
            'categories' => $categories,
            'todayStr' => $todayStr,
        ])->layout('layouts.app', ['title' => 'Tugas & Pekerjaan Rumah']);
    }
}
