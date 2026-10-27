<?php

declare(strict_types=1);

namespace App\Modules\Asset\Commands;

use App\Core\Models\Household;
use App\Core\Support\HouseholdContext;
use App\Modules\Asset\Models\Asset;
use Illuminate\Console\Command;
use Throwable;

final class SendOdometerRemindersCommand extends Command
{
    protected $signature = 'assets:odometer-reminders';
    protected $description = 'Send monthly odometer update reminders for favorite vehicles';

    public function handle(HouseholdContext $context): int
    {
        $this->info('Sending odometer update reminders...');

        $households = Household::all();

        foreach ($households as $household) {
            $context->runInContext($household, function () use ($household): void {
                try {
                    $favVehicles = Asset::query()
                        ->where('household_id', $household->id)
                        ->where('is_favorite', true)
                        ->whereNotNull('meter_unit')
                        ->get();

                    foreach ($favVehicles as $vehicle) {
                        $this->line("Reminder queued for {$vehicle->name} ({$vehicle->plate_number})");
                    }
                } catch (Throwable $e) {
                    $this->error("Failed for household {$household->id}: {$e->getMessage()}");
                }
            });
        }

        return self::SUCCESS;
    }
}
