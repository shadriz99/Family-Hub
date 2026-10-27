<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Integrations;

use App\Core\Alerts\Alert;
use App\Core\Alerts\AlertSource;
use App\Core\Models\Household;
use App\Core\Support\HouseholdContext;
use App\Modules\Organizer\Enums\TaskStatus;
use App\Modules\Organizer\Models\Event;
use App\Modules\Organizer\Models\Task;
use Carbon\CarbonImmutable;
use Throwable;

final class OrganizerAlertSource implements AlertSource
{
    public function key(): string
    {
        return 'organizer_alerts';
    }

    public function alerts(Household $h): iterable
    {
        if (! config('modules.organizer', false)) {
            return [];
        }

        return app(HouseholdContext::class)->runInContext($h, function () use ($h): iterable {
            try {
                $alerts = [];
                $todayStr = CarbonImmutable::now()->toDateString();

                // 1. Overdue tasks
                $overdueCount = Task::query()
                    ->where('household_id', $h->id)
                    ->where('status', TaskStatus::Open)
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', $todayStr)
                    ->count();

                if ($overdueCount > 0) {
                    $alerts[] = new Alert(
                        level: 'danger',
                        title: "Ada {$overdueCount} tugas rumah tangga yang terlambat",
                        url: '/organizer/tasks?tab=today',
                        sourceKey: $this->key(),
                    );
                }

                // 2. Events today
                $eventsToday = Event::query()
                    ->where('household_id', $h->id)
                    ->where('start_date', $todayStr)
                    ->get();

                foreach ($eventsToday as $ev) {
                    $alerts[] = new Alert(
                        level: 'info',
                        title: "Hari ini: {$ev->title}",
                        url: '/calendar',
                        sourceKey: $this->key(),
                    );
                }

                return $alerts;
            } catch (Throwable) {
                return [];
            }
        });
    }
}
