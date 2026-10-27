<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Core\Models\Activity;
use App\Core\Models\Household;
use App\Core\Support\HouseholdContext;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

final class ActivityLogViewer extends Component
{
    /** @return Collection<int, Activity> */
    public function getActivitiesProperty(): Collection
    {
        $household = app(HouseholdContext::class)->currentOrNull();

        if ($household === null) {
            return new Collection();
        }

        /** @var Collection<int, Activity> $activities */
        $activities = Activity::query()
            ->where(function ($query) use ($household): void {
                $query->where(function ($sub) use ($household): void {
                    $sub->where('subject_type', (new Household())->getMorphClass())
                        ->where('subject_id', $household->getKey());
                })->orWhere('properties->household_id', $household->getKey());
            })
            ->with('causer')
            ->latest('id')
            ->take(100)
            ->get();

        return $activities;
    }

    public function render(): View
    {
        return view('livewire.settings.activity-log-viewer', [
            'activities' => $this->getActivitiesProperty(),
        ])->layout('components.app-layout', ['title' => __('Log Aktivitas')]);
    }
}
