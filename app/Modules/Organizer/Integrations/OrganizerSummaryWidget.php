<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Integrations;

use App\Core\Dashboard\DashboardWidget;
use App\Core\Models\Household;
use App\Core\Support\HouseholdContext;
use App\Modules\Organizer\Enums\ShoppingListStatus;
use App\Modules\Organizer\Enums\TaskStatus;
use App\Modules\Organizer\Models\Event;
use App\Modules\Organizer\Models\ShoppingList;
use App\Modules\Organizer\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Throwable;

final class OrganizerSummaryWidget implements DashboardWidget
{
    public function key(): string
    {
        return 'organizer_summary';
    }

    public function module(): string
    {
        return 'organizer';
    }

    public function order(): int
    {
        return 20;
    }

    public function isEnabled(Household $household): bool
    {
        return (bool) config('modules.organizer', false);
    }

    public function render(Household $household): View|string
    {
        return app(HouseholdContext::class)->runInContext($household, function () use ($household): View|string {
            try {
                $todayStr = CarbonImmutable::now()->toDateString();

                $todayTasks = Task::query()
                    ->where('household_id', $household->id)
                    ->where('status', TaskStatus::Open)
                    ->whereNotNull('due_date')
                    ->where('due_date', '<=', $todayStr)
                    ->count();

                $activeShoppingLists = ShoppingList::query()
                    ->where('household_id', $household->id)
                    ->where('status', ShoppingListStatus::Active)
                    ->count();

                $upcomingEvents = Event::query()
                    ->where('household_id', $household->id)
                    ->where('start_date', '>=', $todayStr)
                    ->where('start_date', '<=', CarbonImmutable::now()->addDays(7)->toDateString())
                    ->count();

                return view('modules.organizer.widgets.summary', [
                    'todayTasks' => $todayTasks,
                    'activeShoppingLists' => $activeShoppingLists,
                    'upcomingEvents' => $upcomingEvents,
                ]);
            } catch (Throwable) {
                return '';
            }
        });
    }
}
