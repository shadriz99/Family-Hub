<?php

declare(strict_types=1);

namespace App\Modules\Finance\Console\Commands;

use App\Core\Models\Household;
use App\Modules\Finance\Actions\GeneratePlannedItemsFromRecurringBills;
use App\Modules\Finance\Models\PlannedItem;
use App\Modules\Finance\Services\FinanceReminderService;
use Illuminate\Console\Command;

final class GeneratePlannedItemsCommand extends Command
{
    protected $signature = 'finance:generate-planned-items {--household= : Target specific household ID}';
    protected $description = 'Generates PlannedItems from active RecurringBills up to plan horizon and schedules reminders';

    public function handle(
        GeneratePlannedItemsFromRecurringBills $generator,
        FinanceReminderService $reminderService
    ): int {
        $householdId = $this->option('household');
        $query = Household::query();

        if ($householdId !== null) {
            $query->where('id', $householdId);
        }

        $households = $query->get();
        $totalItems = 0;

        foreach ($households as $household) {
            $created = $generator->execute($household);
            $totalItems += $created;

            // Schedule reminders for upcoming items in window
            $upcomingItems = PlannedItem::query()
                ->where('household_id', $household->id)
                ->where('due_date', '<=', now()->addDays(35)->toDateString())
                ->get();

            foreach ($upcomingItems as $item) {
                $reminderService->scheduleForItem($item);
            }
        }

        $this->info("Generated {$totalItems} planned items across {$households->count()} households.");

        return self::SUCCESS;
    }
}
