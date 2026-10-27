<?php

declare(strict_types=1);

namespace App\Modules\Finance\Integrations;

use App\Core\Calendar\CalendarItem;
use App\Core\Calendar\CalendarSource;
use App\Core\Models\Household;
use App\Core\Support\Money;
use App\Modules\Finance\Enums\Direction;
use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Models\PlannedItem;
use Carbon\CarbonImmutable;

final class PlannedItemCalendarSource implements CalendarSource
{
    public function key(): string
    {
        return 'finance.planned';
    }

    public function label(): string
    {
        return 'Rencana Keuangan';
    }

    /**
     * @return iterable<CalendarItem>
     */
    public function items(Household $h, CarbonImmutable $from, CarbonImmutable $to): iterable
    {
        return app(\App\Core\Support\HouseholdContext::class)->runInContext($h, function () use ($h, $from, $to): iterable {
            try {
                $items = PlannedItem::query()
                    ->where('household_id', $h->id)
                    ->whereIn('status', [
                        PlannedStatus::Planned,
                        PlannedStatus::Partial,
                        PlannedStatus::Paid,
                    ])
                    ->whereBetween('due_date', [
                        $from->toDateString(),
                        $to->toDateString(),
                    ])
                    ->with('category')
                    ->get();
            } catch (\Illuminate\Database\QueryException) {
                return [];
            }

            $result = [];

            foreach ($items as $item) {
                $formattedAmount = Money::format($item->amount);
                $icon = $item->direction === Direction::Expense ? '💸' : '💰';
                $title = "{$icon} {$item->name} · {$formattedAmount}";

                // Color scheme §9.3: overdue #E11D48, planned #0284C7, partial #F59E0B, paid #059669
                $color = match (true) {
                    $item->status === PlannedStatus::Paid => '#059669',
                    $item->status === PlannedStatus::Partial => '#F59E0B',
                    $item->isOverdue() => '#E11D48',
                    default => '#0284C7',
                };

                $url = "/finance/plan?period={$item->period}#item-{$item->id}";

                $result[] = new CalendarItem(
                    uid: "fin-planned-{$item->id}",
                    sourceKey: $this->key(),
                    title: $title,
                    start: $item->due_date->startOfDay(),
                    end: $item->due_date->endOfDay(),
                    allDay: true,
                    color: $color,
                    url: $url,
                    status: $item->status->value,
                    description: $item->category->name,
                );
            }

            return $result;
        });
    }
}
