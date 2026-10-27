<?php

declare(strict_types=1);

namespace App\Modules\Asset\Integrations;

use App\Core\Calendar\CalendarItem;
use App\Core\Calendar\CalendarSource;
use App\Core\Models\Household;
use App\Core\Support\HouseholdContext;
use App\Modules\Asset\Enums\MaintenanceTrigger;
use App\Modules\Asset\Enums\PlanStatus;
use App\Modules\Asset\Models\MaintenancePlan;
use Carbon\CarbonImmutable;
use Throwable;

final class AssetMaintenanceCalendarSource implements CalendarSource
{
    public function key(): string
    {
        return 'asset.maintenance';
    }

    public function label(): string
    {
        return 'Perawatan Aset';
    }

    public function items(Household $h, CarbonImmutable $from, CarbonImmutable $to): iterable
    {
        if (! config('modules.asset', false)) {
            return [];
        }

        return app(HouseholdContext::class)->runInContext($h, function () use ($h, $from, $to): iterable {
            try {
                $plans = MaintenancePlan::query()
                    ->with('asset')
                    ->where('household_id', $h->id)
                    ->where('is_active', true)
                    ->whereIn('trigger', [MaintenanceTrigger::Time, MaintenanceTrigger::TimeOrMeter])
                    ->get();

                $items = [];
                foreach ($plans as $plan) {
                    $effectiveDate = $plan->effectiveDueDate();
                    if (! $effectiveDate) {
                        continue;
                    }

                    if ($effectiveDate->between($from, $to)) {
                        $status = $plan->computedStatus();
                        $color = $status === PlanStatus::Overdue ? '#E11D48' : '#EA580C';
                        $assetName = $plan->asset->name ?? 'Aset';

                        $items[] = new CalendarItem(
                            uid: "ast-plan-{$plan->id}-{$effectiveDate->toDateString()}",
                            sourceKey: $this->key(),
                            title: "🔧 {$plan->name} · {$assetName}",
                            start: $effectiveDate->startOfDay(),
                            end: $effectiveDate->endOfDay(),
                            allDay: true,
                            color: $color,
                            url: url("/assets/{$plan->asset_id}#plan-{$plan->id}"),
                            status: $status->value,
                            description: $plan->instructions,
                        );
                    }
                }

                return $items;
            } catch (Throwable) {
                return [];
            }
        });
    }
}
