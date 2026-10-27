<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Actions;

use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Organizer\Enums\TaskPriority;
use App\Modules\Organizer\Enums\TaskReminderOffset;
use App\Modules\Organizer\Enums\TaskStatus;
use App\Modules\Organizer\Models\Task;
use App\Modules\Organizer\Models\TaskItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateTask
{
    public function __construct(
        private readonly HouseholdContext $context,
    ) {
    }

    /**
     * @param  array{
     *     title: string,
     *     description?: ?string,
     *     assignee_user_id?: ?string,
     *     due_date?: ?string,
     *     due_time?: ?string,
     *     priority?: TaskPriority|string,
     *     rrule?: ?string,
     *     rotate_assignee?: bool,
     *     reminder_offset?: TaskReminderOffset|string|null,
     *     expense_on_complete?: bool,
     *     expense_estimate?: ?int,
     *     expense_category_id?: ?string,
     *     items?: array<int, string|array{text: string, is_done?: bool}>
     * }  $data
     */
    public function handle(User $user, array $data): Task
    {
        $household = $this->context->current();

        return DB::transaction(function () use ($user, $household, $data): Task {
            $priority = $data['priority'] ?? TaskPriority::Normal;
            if (is_string($priority)) {
                $priority = TaskPriority::tryFrom($priority) ?? TaskPriority::Normal;
            }

            $reminderOffset = $data['reminder_offset'] ?? null;
            if (is_string($reminderOffset)) {
                $reminderOffset = TaskReminderOffset::tryFrom($reminderOffset);
            }

            $seriesId = ! empty($data['rrule']) ? (string) Str::ulid() : null;

            $task = new Task();
            $task->household_id = $household->id;
            $task->created_by = $user->id;
            $task->series_id = $seriesId;
            $task->title = trim($data['title']);
            $task->description = $data['description'] ?? null;
            $task->assignee_user_id = $data['assignee_user_id'] ?? null;
            $task->due_date = ! empty($data['due_date']) ? CarbonImmutable::parse($data['due_date']) : null;
            $task->due_time = $data['due_time'] ?? null;
            $task->priority = $priority;
            $task->rrule = ! empty($data['rrule']) ? $data['rrule'] : null;
            $task->rotate_assignee = (bool) ($data['rotate_assignee'] ?? false);
            $task->reminder_offset = $reminderOffset;
            $task->status = TaskStatus::Open;
            $task->expense_on_complete = (bool) ($data['expense_on_complete'] ?? false);
            $task->expense_estimate = $data['expense_estimate'] ?? null;
            $task->expense_category_id = $data['expense_category_id'] ?? null;
            $task->save();

            if (! empty($data['items'])) {
                $sort = 0;
                foreach ($data['items'] as $itemData) {
                    $text = is_array($itemData) ? $itemData['text'] : (string) $itemData;
                    $isDone = is_array($itemData) ? (bool) ($itemData['is_done'] ?? false) : false;

                    if (trim($text) === '') {
                        continue;
                    }

                    $sort++;
                    $item = new TaskItem();
                    $item->task_id = $task->id;
                    $item->text = trim($text);
                    $item->is_done = $isDone;
                    $item->sort_order = $sort;
                    $item->save();
                }
            }

            return $task;
        });
    }
}
