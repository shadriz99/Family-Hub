<?php

declare(strict_types=1);

namespace App\Core\Exporters;

use App\Core\Contracts\HouseholdExporter;
use App\Core\Models\Household;

final class CoreHouseholdExporter implements HouseholdExporter
{
    public function module(): string
    {
        return 'core';
    }

    /**
     * @return array<string, mixed>
     */
    public function export(Household $household): array
    {
        $members = $household->members()
            ->with('user')
            ->orderBy('slot')
            ->get()
            ->map(fn ($member): array => [
                'slot' => $member->slot,
                'role' => $member->role->value,
                'joined_at' => $member->joined_at?->toIso8601String(),
                'user' => [
                    'name' => $member->user?->name,
                    'email' => $member->user?->email,
                ],
            ]);

        $periodSettings = $household->financePeriodSettings()
            ->orderBy('effective_on')
            ->get()
            ->map(fn ($setting): array => [
                'start_day' => $setting->start_day,
                'effective_on' => $setting->effective_on->toDateString(),
            ]);

        return [
            'core/household.json' => [
                'id' => $household->getKey(),
                'name' => $household->name,
                'timezone' => $household->timezone,
                'finance_period_start_day' => $household->finance_period_start_day,
                'created_at' => $household->created_at?->toIso8601String(),
            ],
            'core/members.json' => $members->all(),
            'core/period_settings.json' => $periodSettings->all(),
        ];
    }
}
