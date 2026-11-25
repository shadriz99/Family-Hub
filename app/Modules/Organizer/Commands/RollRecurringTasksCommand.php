<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Commands;

use App\Core\Models\Household;
use App\Core\Models\HouseholdMember;
use App\Core\Recurrence\Recurrence;
use App\Core\Support\HouseholdContext;
use App\Modules\Organizer\Enums\TaskStatus;
use App\Modules\Organizer\Models\Task;
use App\Modules\Organizer\Models\TaskItem;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class RollRecurringTasksCommand extends Command
{
    protected $signature = 'organizer:roll-recurring-tasks';

    protected $description = 'Ensures every active recurring task series has exactly one open instance (BR-ORG-001)';

    public function handle(HouseholdContext $context): int
    {
        $this->info('Checking recurring task series across all households...');

        $distinctSeries = DB::table('org_tasks')
            ->select('household_id', 'series_id')
            ->whereNotNull('series_id')
            ->whereNotNull('rrule')
            ->whereNull('deleted_at')
            ->distinct()
            ->get();

        $rolledCount = 0;

        foreach ($distinctSeries as $row) {
            $household = Household::find($row->household_id);
            if ($household === null) {
                continue;
            }

            $context->runInContext($household, function () use ($household, $row, &$rolledCount): void {
                // Check if an open instance already exists (BR-ORG-001)
                $hasOpen = Task::query()
                    ->where('household_id', $household->id)
                    ->where('series_id', $row->series_id)
                    ->where('status', TaskStatus::Open)
                    ->exists();

                if ($hasOpen) {
                    return;
                }

                // Get the most recent task in the series
                $lastTask = Task::query()
                    ->where('household_id', $household->id)
                    ->where('series_id', $row->series_id)
                    ->orderByDesc('due_date')
                    ->first();

                if ($lastTask === null || empty($lastTask->rrule)) {
                    return;
                }

                $currentDueDate = $lastTask->due_date ?? CarbonImmutable::now();
                $nextOccurrence = Recurrence::next($lastTask->rrule, $currentDueDate, CarbonImmutable::now());

                if ($nextOccurrence === null) {
                    return;
                }

                // Determine next assignee with rotation if requested
                $nextAssigneeId = $lastTask->assignee_user_id;
                if ($lastTask->rotate_assignee) {
                    $members = HouseholdMember::query()
                        ->where('household_id', $household->id)
                        ->pluck('user_id')
                        ->values();

                    if ($members->count() > 1) {
                        $otherMember = $members->first(fn (string $id): bool => $id !== $lastTask->assignee_user_id);
                        if ($otherMember !== null) {
                            $nextAssigneeId = $otherMember;
                        }
                    }
                }

                // Create the next open instance
                $nextTask = new Task();
                $nextTask->household_id = $household->id;
                $nextTask->created_by = $lastTask->created_by;
                $nextTask->series_id = $lastTask->series_id;
                $nextTask->title = $lastTask->title;
                $nextTask->description = $lastTask->description;
                $nextTask->assignee_user_id = $nextAssigneeId;
                $nextTask->due_date = $nextOccurrence;
                $nextTask->due_time = $lastTask->due_time;
                $nextTask->priority = $lastTask->priority;
                $nextTask->rrule = $lastTask->rrule;
                $nextTask->rotate_assignee = $lastTask->rotate_assignee;
                $nextTask->save();

                // Copy items
                foreach ($lastTask->items as $item) {
                    $newItem = new TaskItem();
                    $newItem->task_id = $nextTask->id;
                    $newItem->text = $item->text;
                    $newItem->is_done = false;
                    $newItem->position = $item->position;
                    $newItem->save();
                }

                $this->line("Generated next recurring task '{$nextTask->title}' for series {$row->series_id} in {$household->name}");
                $rolledCount++;
            });
        }

        $this->info("Recurring tasks roll complete: {$rolledCount} new instances created.");

        return Command::SUCCESS;
    }
}
