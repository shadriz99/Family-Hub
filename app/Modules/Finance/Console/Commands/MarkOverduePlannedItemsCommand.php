<?php

declare(strict_types=1);

namespace App\Modules\Finance\Console\Commands;

use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Models\PlannedItem;
use App\Modules\Finance\Services\FinanceReminderService;
use Illuminate\Console\Command;

final class MarkOverduePlannedItemsCommand extends Command
{
    protected $signature = 'finance:mark-overdue';
    protected $description = 'Schedules overdue reminders for unpaid past-due PlannedItems';

    public function handle(FinanceReminderService $reminderService): int
    {
        $today = now()->toDateString();

        $overdueItems = PlannedItem::query()
            ->whereIn('status', [PlannedStatus::Planned, PlannedStatus::Partial])
            ->where('due_date', '<', $today)
            ->get();

        $count = 0;
        foreach ($overdueItems as $item) {
            $reminderService->scheduleForItem($item);
            $count++;
        }

        $this->info("Processed {$count} overdue planned items.");

        return self::SUCCESS;
    }
}
