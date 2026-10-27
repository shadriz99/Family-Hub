<?php

declare(strict_types=1);

namespace App\Modules\Finance\Console\Commands;

use App\Core\Models\Household;
use App\Core\Support\PeriodResolver;
use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Models\PlannedItem;
use Illuminate\Console\Command;

final class ApplyPeriodSettingsCommand extends Command
{
    protected $signature = 'finance:apply-period-settings';
    protected $description = 'Applies scheduled finance period settings at period boundaries';

    public function handle(): int
    {
        $households = Household::query()->get();
        $updatedCount = 0;

        foreach ($households as $household) {
            $resolver = PeriodResolver::for($household);
            $current = $resolver->current();

            // Recompute period key for future non-historical planned items that are not yet paid
            $items = PlannedItem::query()
                ->where('household_id', $household->id)
                ->whereIn('status', [PlannedStatus::Planned, PlannedStatus::Partial])
                ->where('due_date', '>=', $current->start->toDateString())
                ->get();

            foreach ($items as $item) {
                $newPeriod = $resolver->forDate($item->due_date)->key;
                if ($item->period !== $newPeriod) {
                    $item->period = $newPeriod;
                    $item->save();
                    $updatedCount++;
                }
            }
        }

        $this->info("Period settings checked. Updated {$updatedCount} future planned items.");

        return self::SUCCESS;
    }
}
