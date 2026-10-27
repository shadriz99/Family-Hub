<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Livewire\Calendar;

use App\Core\Calendar\CalendarAggregator;
use App\Core\Calendar\CalendarItem;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Organizer\Enums\EventKind;
use App\Modules\Organizer\Models\Event;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

final class Index extends Component
{
    #[Url]
    public string $view = 'month'; // month, agenda

    #[Url]
    public int $year;

    #[Url]
    public int $month;

    #[Url]
    public string $memberFilter = 'all'; // all, me, partner

    /** @var array<string, bool> */
    public array $sourceFilters = [
        'events' => true,
        'tasks' => true,
        'finance_planned' => true,
        'asset_maintenance' => true,
    ];

    public ?string $selectedDate = null;

    // Quick add event modal
    public bool $showEventModal = false;
    public string $eventTitle = '';
    public string $eventKind = 'event';
    public ?string $eventStartDate = null;
    public bool $eventAllDay = true;
    public ?string $eventLocation = null;
    public ?string $eventRrule = null;

    public function mount(): void
    {
        $now = CarbonImmutable::now();
        $this->year ??= $now->year;
        $this->month ??= $now->month;
        $this->selectedDate = $now->toDateString();
        $this->eventStartDate = $now->toDateString();
    }

    public function previousMonth(): void
    {
        $dt = CarbonImmutable::create($this->year, $this->month, 1)->subMonth();
        $this->year = $dt->year;
        $this->month = $dt->month;
    }

    public function nextMonth(): void
    {
        $dt = CarbonImmutable::create($this->year, $this->month, 1)->addMonth();
        $this->year = $dt->year;
        $this->month = $dt->month;
    }

    public function goToToday(): void
    {
        $now = CarbonImmutable::now();
        $this->year = $now->year;
        $this->month = $now->month;
        $this->selectedDate = $now->toDateString();
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
    }

    public function toggleSource(string $key): void
    {
        $this->sourceFilters[$key] = ! ($this->sourceFilters[$key] ?? false);
    }

    public function createQuickEvent(): void
    {
        $this->validate([
            'eventTitle' => ['required', 'string', 'max:150'],
            'eventStartDate' => ['required', 'date'],
            'eventKind' => ['required', 'in:event,birthday,anniversary'],
        ]);

        /** @var User $user */
        $user = auth()->user();
        $household = app(HouseholdContext::class)->current();

        $rrule = match ($this->eventKind) {
            'birthday', 'anniversary' => 'FREQ=YEARLY',
            default => $this->eventRrule,
        };

        Event::create([
            'household_id' => $household->id,
            'created_by' => $user->id,
            'kind' => EventKind::from($this->eventKind),
            'title' => trim($this->eventTitle),
            'start_date' => $this->eventStartDate,
            'end_date' => CarbonImmutable::parse($this->eventStartDate)->addDay()->toDateString(),
            'all_day' => $this->eventAllDay,
            'location' => $this->eventLocation,
            'rrule' => $rrule,
        ]);

        $this->eventTitle = '';
        $this->eventLocation = null;
        $this->showEventModal = false;
    }

    public function render(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $household = app(HouseholdContext::class)->current();

        // Active source keys
        $activeSources = array_keys(array_filter($this->sourceFilters));

        $calendarAggregator = app(CalendarAggregator::class);

        if ($this->view === 'agenda') {
            $from = CarbonImmutable::now()->startOfDay();
            $to = $from->addDays(60)->endOfDay();
        } else {
            // Month view: include padding days to full 7-column grid
            $startOfMonth = CarbonImmutable::create($this->year, $this->month, 1)->startOfDay();
            $endOfMonth = $startOfMonth->endOfMonth()->endOfDay();

            // Monday as start of week (ISO-8601)
            $from = $startOfMonth->startOfWeek(CarbonImmutable::MONDAY)->startOfDay();
            $to = $endOfMonth->endOfWeek(CarbonImmutable::SUNDAY)->endOfDay();
        }

        /** @var Collection<int, CalendarItem> $allItems */
        $allItems = collect($calendarAggregator->between($household, $from, $to, $activeSources));

        // Filter by member
        if ($this->memberFilter === 'me') {
            $allItems = $allItems->filter(fn (CalendarItem $i) => $i->memberId === null || $i->memberId === $user->id);
        } elseif ($this->memberFilter === 'partner') {
            $allItems = $allItems->filter(fn (CalendarItem $i) => $i->memberId === null || $i->memberId !== $user->id);
        }

        // Group items by date string (YYYY-MM-DD)
        $itemsByDate = $allItems->groupBy(fn (CalendarItem $item) => $item->start->toDateString());

        // Build grid of days for month view
        $gridDays = [];
        if ($this->view === 'month') {
            $cursor = $from;
            while ($cursor->lte($to)) {
                $dateKey = $cursor->toDateString();
                $gridDays[] = [
                    'date' => $cursor,
                    'dateString' => $dateKey,
                    'isCurrentMonth' => $cursor->month === $this->month,
                    'isToday' => $cursor->isToday(),
                    'items' => $itemsByDate->get($dateKey, collect()),
                ];
                $cursor = $cursor->addDay();
            }
        }

        // Selected date items
        $selectedDateItems = $this->selectedDate ? $itemsByDate->get($this->selectedDate, collect()) : collect();

        $monthName = CarbonImmutable::create($this->year, $this->month, 1)->translatedFormat('F Y');

        return view('modules.organizer.calendar.index', [
            'gridDays' => $gridDays,
            'itemsByDate' => $itemsByDate,
            'selectedDateItems' => $selectedDateItems,
            'monthName' => $monthName,
        ])->layout('layouts.app', ['title' => 'Kalender Terpadu']);
    }
}
