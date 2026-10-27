<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Services\ReminderService;
use Illuminate\Console\Command;

final class DispatchRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:dispatch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch pending reminder deliveries that are due';

    /**
     * Execute the console command.
     */
    public function handle(ReminderService $reminderService): int
    {
        $stats = $reminderService->dispatchDueDeliveries();

        $this->info("Reminder deliveries dispatched: {$stats['dispatched']}, retried: {$stats['retried']}, failed: {$stats['failed']}.");

        return self::SUCCESS;
    }
}
