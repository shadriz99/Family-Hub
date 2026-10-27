<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Http\Controllers;

use App\Core\Calendar\CalendarAggregator;
use App\Core\Calendar\CalendarItem;
use App\Core\Support\HouseholdContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event as ICalEvent;
use Spatie\IcalendarGenerator\Components\Timezone;

final class CalendarExportController extends Controller
{
    public function __invoke(Request $request, CalendarAggregator $aggregator, HouseholdContext $context): Response
    {
        $household = $context->current();

        $fromStr = $request->query('from', CarbonImmutable::now()->toDateString());
        $toStr = $request->query('to', CarbonImmutable::now()->addMonths(3)->toDateString());

        $from = CarbonImmutable::parse((string) $fromStr)->startOfDay();
        $to = CarbonImmutable::parse((string) $toStr)->endOfDay();

        // Enforce max 400 days limit per BR-ORG-040
        if ($from->diffInDays($to) > 400) {
            $to = $from->addDays(400)->endOfDay();
        }

        $sources = $request->has('sources') ? (array) $request->query('sources') : null;
        $hideAmounts = $request->boolean('hide_amounts', true);
        $memberId = $request->query('member');

        $items = collect($aggregator->between($household, $from, $to, $sources));

        if ($memberId) {
            $items = $items->filter(fn (CalendarItem $i) => $i->memberId === null || $i->memberId === $memberId);
        }

        // Limit to 5000 items (BR-ORG-040)
        $items = $items->take(5000);

        $timezoneName = $household->timezone ?: 'Asia/Jakarta';
        $calendar = Calendar::create("Family Hub - {$household->name}")
            ->timezone(Timezone::create($timezoneName));

        foreach ($items as $item) {
            $title = $item->title;
            if ($hideAmounts) {
                // Remove nominal like (Rp 1.500.000) or Rp 50.000
                $title = (string) preg_replace('/\s*\(?Rp\s*[\d\.,]+\)?/i', '', $title);
            }

            $event = ICalEvent::create($title)
                ->uniqueIdentifier($item->uid);

            if ($item->allDay) {
                $event->startsAt($item->start->toDateTime(), false)
                    ->fullDay();
            } else {
                $event->startsAt($item->start->toDateTime(), true)
                    ->endsAt($item->end ? $item->end->toDateTime() : $item->start->addHour()->toDateTime(), true);
            }

            if ($item->description) {
                $event->description($item->description);
            }

            $calendar->event($event);
        }

        $slug = Str::slug($household->name);
        $filename = "familyhub-{$slug}-{$from->toDateString()}-{$to->toDateString()}.ics";

        return response($calendar->get(), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
