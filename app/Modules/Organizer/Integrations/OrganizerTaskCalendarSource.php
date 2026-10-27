<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Integrations;

use App\Core\Calendar\CalendarItem;
use App\Core\Calendar\CalendarSource;
use App\Core\Models\Household;
use App\Core\Support\HouseholdContext;
use App\Modules\Organizer\Enums\TaskStatus;
use App\Modules\Organizer\Models\Task;
use Carbon\CarbonImmutable;
use Throwable;

final class OrganizerTaskCalendarSource implements CalendarSource
{
    public function key(): string
    {
        return 'tasks';
    }

    public function label(): string
    {
        return 'Tugas Rumah Tangga';
    }

    public function items(Household $h, CarbonImmutable $from, CarbonImmutable $to): iterable
    {
        if (! config('modules.organizer', false)) {
            return [];
        }

        return app(HouseholdContext::class)->runInContext($h, function () use ($h, $from, $to): iterable {
            try {
                // BR-ORG-005: Tasks without due_date do not appear on calendar
                $tasks = Task::query()
                    ->where('household_id', $h->id)
                    ->whereNotNull('due_date')
                    ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
                    ->get();

                $items = [];

                foreach ($tasks as $task) {
                    if ($task->due_date === null) {
                        continue;
                    }

                    $dueDate = $task->due_date;
                    $start = $task->due_time
                        ? $dueDate->setTimeFromTimeString($task->due_time)
                        : $dueDate->startOfDay();
                    $end = $task->due_time
                        ? $start->addHour()
                        : $dueDate->endOfDay();

                    $items[] = new CalendarItem(
                        uid: "org_task_{$task->id}",
                        sourceKey: $this->key(),
                        title: "📋 {$task->title}",
                        start: $start,
                        end: $end,
                        allDay: $task->due_time === null,
                        color: $task->status === TaskStatus::Done ? '#10b981' : '#f59e0b',
                        url: '/organizer/tasks',
                        status: $task->status->value,
                        description: $task->description,
                        memberId: $task->assignee_user_id,
                    );
                }

                return $items;
            } catch (Throwable) {
                return [];
            }
        });
    }
}
