<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Integrations;

use App\Core\Calendar\CalendarItem;
use App\Core\Calendar\CalendarSource;
use App\Core\Models\Household;
use App\Core\Recurrence\Recurrence;
use App\Core\Support\HouseholdContext;
use App\Modules\Organizer\Enums\EventKind;
use App\Modules\Organizer\Models\Event;
use App\Modules\Organizer\Models\EventException;
use Carbon\CarbonImmutable;
use Throwable;

final class OrganizerEventCalendarSource implements CalendarSource
{
    public function key(): string
    {
        return 'events';
    }

    public function label(): string
    {
        return 'Acara Keluarga';
    }

    public function items(Household $h, CarbonImmutable $from, CarbonImmutable $to): iterable
    {
        if (! config('modules.organizer', false)) {
            return [];
        }

        return app(HouseholdContext::class)->runInContext($h, function () use ($h, $from, $to): iterable {
            try {
                $events = Event::query()
                    ->with('exceptions')
                    ->where('household_id', $h->id)
                    ->get();

                $items = [];

                foreach ($events as $event) {
                    $exceptionsByDate = $event->exceptions->keyBy(fn (EventException $e): string => $e->original_date->toDateString());

                    if (! empty($event->rrule)) {
                        // Expanding recurring event (BR-ORG-030)
                        $dtstart = $event->start_date;
                        $occurrences = Recurrence::occurrencesBetween($event->rrule, $dtstart, $from, $to);

                        foreach ($occurrences as $occ) {
                            $dateStr = $occ->toDateString();

                            // Check exception
                            if ($exceptionsByDate->has($dateStr)) {
                                /** @var EventException $ex */
                                $ex = $exceptionsByDate->get($dateStr);
                                if ($ex->is_cancelled) {
                                    continue;
                                }
                            }

                            $title = $event->title;
                            if ($event->kind === EventKind::Birthday && $event->birth_year) {
                                $age = $occ->year - $event->birth_year;
                                $title = "🎂 Ulang tahun {$event->title} ({$age} thn)";
                            } elseif ($event->kind === EventKind::Birthday) {
                                $title = "🎂 Ulang tahun {$event->title}";
                            } elseif ($event->kind === EventKind::Anniversary) {
                                $title = "💍 Anniversary {$event->title}";
                            }

                            $items[] = new CalendarItem(
                                uid: "org_event_{$event->id}_{$dateStr}",
                                sourceKey: $this->key(),
                                title: $title,
                                start: $occ->startOfDay(),
                                end: $occ->endOfDay(),
                                allDay: $event->all_day,
                                color: $event->color ?: '#4f46e5',
                                url: "/organizer/events/{$event->id}",
                                status: 'confirmed',
                                description: $event->description,
                                memberId: $event->for_user_id,
                            );
                        }
                    } else {
                        // Single event
                        $eventDate = $event->start_date;
                        if ($eventDate->gte($from->startOfDay()) && $eventDate->lte($to->endOfDay())) {
                            $items[] = new CalendarItem(
                                uid: "org_event_{$event->id}",
                                sourceKey: $this->key(),
                                title: $event->title,
                                start: $event->start_at ?? $eventDate->startOfDay(),
                                end: $event->end_at ?? $eventDate->endOfDay(),
                                allDay: $event->all_day,
                                color: $event->color ?: '#4f46e5',
                                url: "/organizer/events/{$event->id}",
                                status: 'confirmed',
                                description: $event->description,
                                memberId: $event->for_user_id,
                            );
                        }
                    }
                }

                return $items;
            } catch (Throwable) {
                return [];
            }
        });
    }
}
