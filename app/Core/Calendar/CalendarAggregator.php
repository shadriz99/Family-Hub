<?php

declare(strict_types=1);

namespace App\Core\Calendar;

use App\Core\Models\Household;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

final class CalendarAggregator
{
    public const MAX_DAYS_RANGE = 400;

    /**
     * @param iterable<CalendarSource>|null $sources
     */
    public function __construct(
        private readonly ?Container $container = null,
        private readonly ?iterable $sources = null,
    ) {
    }

    /**
     * @param array<string>|null $sourceKeys
     * @return array<CalendarItem>
     */
    public function between(
        Household $household,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?array $sourceKeys = null
    ): array {
        if ($from->greaterThan($to)) {
            throw new InvalidArgumentException('Start date ($from) cannot be after end date ($to).');
        }

        if ($from->diffInDays($to) > self::MAX_DAYS_RANGE) {
            throw new InvalidArgumentException(sprintf('Date range exceeds maximum allowed range of %d days.', self::MAX_DAYS_RANGE));
        }

        $timezone = $household->timezone;
        if ($from->timezone->getName() !== $timezone) {
            $from = $from->setTimezone($timezone);
        }
        if ($to->timezone->getName() !== $timezone) {
            $to = $to->setTimezone($timezone);
        }

        $activeSources = $this->resolveSources();

        $items = [];
        foreach ($activeSources as $source) {
            if ($sourceKeys !== null && ! in_array($source->key(), $sourceKeys, true)) {
                continue;
            }

            foreach ($source->items($household, $from, $to) as $item) {
                $items[] = $item;
            }
        }

        usort($items, function (CalendarItem $a, CalendarItem $b): int {
            $cmp = $a->start->getTimestamp() <=> $b->start->getTimestamp();
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp($a->title, $b->title);
        });

        return $items;
    }

    /**
     * @return iterable<CalendarSource>
     */
    private function resolveSources(): iterable
    {
        if ($this->sources !== null) {
            return $this->sources;
        }

        if ($this->container !== null) {
            /** @var iterable<CalendarSource> $tagged */
            $tagged = $this->container->tagged('calendar.sources');

            return $tagged;
        }

        return [];
    }
}
