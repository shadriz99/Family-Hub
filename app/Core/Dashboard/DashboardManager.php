<?php

declare(strict_types=1);

namespace App\Core\Dashboard;

use App\Core\Alerts\Alert;
use App\Core\Alerts\AlertAggregator;
use App\Core\Calendar\CalendarAggregator;
use App\Core\Calendar\CalendarItem;
use App\Core\Models\Household;
use Carbon\CarbonImmutable;

final class DashboardManager
{
    /**
     * @param iterable<DashboardWidget> $widgets
     */
    public function __construct(
        private readonly iterable $widgets,
        private readonly AlertAggregator $alertAggregator,
        private readonly CalendarAggregator $calendarAggregator,
    ) {
    }

    /**
     * @return array<int, DashboardWidget>
     */
    public function widgets(Household $household): array
    {
        $active = [];

        foreach ($this->widgets as $widget) {
            $module = $widget->module();

            // Module flag check: 'core' is always active; others follow config('modules.*')
            $isModuleActive = $module === 'core' || (bool) config("modules.{$module}", false);

            if ($isModuleActive && $widget->isEnabled($household)) {
                $active[] = $widget;
            }
        }

        usort($active, fn (DashboardWidget $a, DashboardWidget $b): int => $a->order() <=> $b->order());

        return $active;
    }

    /**
     * @return array<int, Alert>
     */
    public function alerts(Household $household): array
    {
        return $this->alertAggregator->for($household, 5);
    }

    /**
     * Returns calendar items for today and tomorrow in household timezone.
     *
     * @return array<int, CalendarItem>
     */
    public function todayAndTomorrow(Household $household, ?CarbonImmutable $now = null): array
    {
        $timezone = $household->timezone ?? 'Asia/Jakarta';
        $nowLocal = ($now ?? CarbonImmutable::now('UTC'))->setTimezone($timezone);

        $start = $nowLocal->startOfDay()->setTimezone('UTC');
        $end = $nowLocal->addDay()->endOfDay()->setTimezone('UTC');

        return $this->calendarAggregator->between($household, $start, $end);
    }

    /**
     * Returns upcoming items for days +2 to +8 (max 7 items) in household timezone.
     *
     * @return array<int, CalendarItem>
     */
    public function upcomingWeek(Household $household, ?CarbonImmutable $now = null): array
    {
        $timezone = $household->timezone ?? 'Asia/Jakarta';
        $nowLocal = ($now ?? CarbonImmutable::now('UTC'))->setTimezone($timezone);

        $start = $nowLocal->addDays(2)->startOfDay()->setTimezone('UTC');
        $end = $nowLocal->addDays(8)->endOfDay()->setTimezone('UTC');

        $items = $this->calendarAggregator->between($household, $start, $end);

        return array_slice($items, 0, 7);
    }

    /**
     * Localized time-of-day greeting in the user/household timezone.
     */
    public function greeting(string $name, ?CarbonImmutable $now = null, string $timezone = 'Asia/Jakarta'): string
    {
        $local = ($now ?? CarbonImmutable::now('UTC'))->setTimezone($timezone);
        $hour = $local->hour;

        $salutation = match (true) {
            $hour >= 4 && $hour < 11 => __('Selamat pagi'),
            $hour >= 11 && $hour < 15 => __('Selamat siang'),
            $hour >= 15 && $hour < 18 => __('Selamat sore'),
            default => __('Selamat malam'),
        };

        return "{$salutation}, {$name}";
    }
}
