<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Livewire\Tasks;

use App\Core\Models\HouseholdMember;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Finance\Contracts\FinanceGateway;
use App\Modules\Organizer\Actions\CreateTask;
use App\Modules\Organizer\Enums\TaskPriority;
use App\Modules\Organizer\Enums\TaskReminderOffset;
use App\Modules\Organizer\Models\Task;
use App\Modules\Organizer\Models\TaskItem;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class Form extends Component
{
    public ?string $taskId = null;
    public string $title = '';
    public ?string $description = null;
    public ?string $assignee_user_id = null;
    public ?string $due_date = null;
    public ?string $due_time = null;
    public string $priority = 'normal';
    public ?string $recurrencePreset = 'none'; // none, daily, weekly, monthly, yearly
    public ?string $customRrule = null;
    public bool $rotate_assignee = false;
    public ?string $reminder_offset = null;
    public bool $expense_on_complete = false;
    public ?int $expense_estimate = null;
    public ?string $expense_category_id = null;

    /** @var array<int, array{id?: string, text: string, is_done: bool}> */
    public array $items = [];

    public function mount(?string $id = null): void
    {
        if ($id !== null) {
            $task = Task::query()->with('items')->findOrFail($id);
            $this->taskId = $task->id;
            $this->title = $task->title;
            $this->description = $task->description;
            $this->assignee_user_id = $task->assignee_user_id;
            $this->due_date = $task->due_date?->toDateString();
            $this->due_time = $task->due_time ? substr($task->due_time, 0, 5) : null;
            $this->priority = $task->priority->value;
            $this->rotate_assignee = $task->rotate_assignee;
            $this->reminder_offset = $task->reminder_offset?->value;
            $this->expense_on_complete = $task->expense_on_complete;
            $this->expense_estimate = $task->expense_estimate;
            $this->expense_category_id = $task->expense_category_id;

            if ($task->rrule) {
                $this->customRrule = $task->rrule;
                $this->recurrencePreset = match ($task->rrule) {
                    'FREQ=DAILY' => 'daily',
                    'FREQ=WEEKLY' => 'weekly',
                    'FREQ=MONTHLY' => 'monthly',
                    'FREQ=YEARLY' => 'yearly',
                    default => 'custom',
                };
            }

            foreach ($task->items as $item) {
                $this->items[] = [
                    'id' => $item->id,
                    'text' => $item->text,
                    'is_done' => $item->is_done,
                ];
            }
        }
    }

    public function addItem(): void
    {
        $this->items[] = [
            'text' => '',
            'is_done' => false,
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'due_time' => ['nullable', 'string'],
            'priority' => ['required', 'in:low,normal,high'],
            'expense_estimate' => ['nullable', 'integer', 'min:0'],
        ]);

        /** @var User $user */
        $user = auth()->user();
        $household = app(HouseholdContext::class)->current();

        $rrule = match ($this->recurrencePreset) {
            'daily' => 'FREQ=DAILY',
            'weekly' => 'FREQ=WEEKLY',
            'monthly' => 'FREQ=MONTHLY',
            'yearly' => 'FREQ=YEARLY',
            'custom' => $this->customRrule,
            default => null,
        };

        if ($this->taskId !== null) {
            // Update existing
            $task = Task::query()->findOrFail($this->taskId);
            $task->title = $this->title;
            $task->description = $this->description;
            $task->assignee_user_id = $this->assignee_user_id;
            $task->due_date = $this->due_date ? CarbonImmutable::parse($this->due_date) : null;
            $task->due_time = $this->due_time;
            $task->priority = TaskPriority::from($this->priority);
            $task->rrule = $rrule;
            $task->rotate_assignee = $this->rotate_assignee;
            $task->reminder_offset = $this->reminder_offset ? TaskReminderOffset::from($this->reminder_offset) : null;
            $task->expense_on_complete = $this->expense_on_complete;
            $task->expense_estimate = $this->expense_estimate;
            $task->expense_category_id = $this->expense_category_id;
            $task->save();

            // Sync checklist items
            $existingIds = [];
            foreach ($this->items as $idx => $itemData) {
                if (trim($itemData['text']) === '') {
                    continue;
                }
                if (! empty($itemData['id'])) {
                    $item = TaskItem::query()->find($itemData['id']);
                    if ($item !== null) {
                        $item->text = trim($itemData['text']);
                        $item->sort_order = $idx + 1;
                        $item->save();
                        $existingIds[] = $item->id;
                    }
                } else {
                    $newItem = new TaskItem();
                    $newItem->task_id = $task->id;
                    $newItem->text = trim($itemData['text']);
                    $newItem->is_done = false;
                    $newItem->sort_order = $idx + 1;
                    $newItem->save();
                    $existingIds[] = $newItem->id;
                }
            }
            TaskItem::query()->where('task_id', $task->id)->whereNotIn('id', $existingIds)->delete();
        } else {
            // Create new
            app(CreateTask::class)->handle($user, [
                'title' => $this->title,
                'description' => $this->description,
                'assignee_user_id' => $this->assignee_user_id,
                'due_date' => $this->due_date,
                'due_time' => $this->due_time,
                'priority' => $this->priority,
                'rrule' => $rrule,
                'rotate_assignee' => $this->rotate_assignee,
                'reminder_offset' => $this->reminder_offset,
                'expense_on_complete' => $this->expense_on_complete,
                'expense_estimate' => $this->expense_estimate,
                'expense_category_id' => $this->expense_category_id,
                'items' => $this->items,
            ]);
        }

        $this->redirect('/organizer/tasks', navigate: true);
    }

    public function render(): View
    {
        $household = app(HouseholdContext::class)->current();
        $members = HouseholdMember::query()
            ->with('user')
            ->where('household_id', $household->id)
            ->get();

        $gateway = app(FinanceGateway::class);
        $categories = $gateway->expenseCategoryOptions();

        return view('modules.organizer.tasks.form', [
            'members' => $members,
            'categories' => $categories,
        ])->layout('layouts.app', ['title' => $this->taskId ? 'Edit Tugas' : 'Tambah Tugas Baru']);
    }
}
