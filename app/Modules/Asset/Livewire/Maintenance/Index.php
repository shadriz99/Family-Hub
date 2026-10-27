<?php

declare(strict_types=1);

namespace App\Modules\Asset\Livewire\Maintenance;

use App\Core\Support\HouseholdContext;
use App\Modules\Asset\Models\MaintenancePlan;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Index extends Component
{
    // Snooze Modal
    public bool $showSnoozeModal = false;
    public ?string $snoozePlanId = null;
    public ?int $snoozeDays = 7;

    public function openSnooze(string $planId): void
    {
        $this->snoozePlanId = $planId;
        $this->snoozeDays = 7;
        $this->showSnoozeModal = true;
    }

    public function saveSnooze(): void
    {
        if (! $this->snoozePlanId) {
            return;
        }

        $plan = MaintenancePlan::where('id', $this->snoozePlanId)->first();
        if ($plan) {
            $base = $plan->effectiveDueDate() ?? CarbonImmutable::today();
            $plan->snoozed_until = $base->addDays($this->snoozeDays ?? 7);
            $plan->save();
        }

        $this->showSnoozeModal = false;
    }

    public function render(): View
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        $today = CarbonImmutable::today();

        $plans = MaintenancePlan::query()
            ->with(['asset.location', 'vendor'])
            ->where('household_id', $household?->id)
            ->where('is_active', true)
            ->get();

        $grouped = [
            'overdue' => [],
            'due' => [],
            'soon' => [],
            'ok' => [],
        ];

        foreach ($plans as $plan) {
            $status = $plan->computedStatus($today);
            $grouped[$status->value][] = $plan;
        }

        return view('modules.asset.maintenance.index', [
            'grouped' => $grouped,
            'totalCount' => $plans->count(),
        ]);
    }
}
