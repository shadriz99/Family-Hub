<?php

declare(strict_types=1);

namespace App\Modules\Asset\Integrations;

use App\Core\Calendar\CalendarItem;
use App\Core\Calendar\CalendarSource;
use App\Core\Models\Household;
use App\Core\Support\HouseholdContext;
use App\Modules\Asset\Enums\IssueStatus;
use App\Modules\Asset\Models\Issue;
use Carbon\CarbonImmutable;
use Throwable;

final class AssetIssueCalendarSource implements CalendarSource
{
    public function key(): string
    {
        return 'asset.issues';
    }

    public function label(): string
    {
        return 'Jadwal Teknisi / Kerusakan';
    }

    public function items(Household $h, CarbonImmutable $from, CarbonImmutable $to): iterable
    {
        if (! config('modules.asset', false)) {
            return [];
        }

        return app(HouseholdContext::class)->runInContext($h, function () use ($h, $from, $to): iterable {
            try {
                $issues = Issue::query()
                    ->where('household_id', $h->id)
                    ->where('status', IssueStatus::Scheduled)
                    ->whereNotNull('scheduled_on')
                    ->whereBetween('scheduled_on', [$from->toDateString(), $to->toDateString()])
                    ->get();

                $items = [];
                foreach ($issues as $issue) {
                    if (! $issue->scheduled_on) {
                        continue;
                    }

                    $items[] = new CalendarItem(
                        uid: "ast-issue-{$issue->id}",
                        sourceKey: $this->key(),
                        title: "🛠️ {$issue->title} (teknisi)",
                        start: $issue->scheduled_on->startOfDay(),
                        end: $issue->scheduled_on->endOfDay(),
                        allDay: true,
                        color: '#0891B2',
                        url: url("/assets/issues/{$issue->id}"),
                        status: $issue->status->value,
                        description: $issue->description,
                    );
                }

                return $items;
            } catch (Throwable) {
                return [];
            }
        });
    }
}
