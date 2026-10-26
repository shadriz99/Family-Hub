<?php

declare(strict_types=1);

namespace App\Core\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

final class PeriodResolver
{
    /** @var list<array{effective_on: CarbonImmutable, start_day: int}> */
    private array $settings = [];

    /**
     * @param list<array{effective_on: string, start_day: int}> $history
     */
    public function __construct(array $history, private readonly string $timezone)
    {
        new DateTimeZone($timezone);

        if ($history === []) {
            throw new InvalidArgumentException('Finance period history is required.');
        }

        foreach ($history as $row) {
            $effective = $this->date($row['effective_on']);
            $previous = $this->settings === [] ? null : $this->settings[array_key_last($this->settings)];

            if ($row['start_day'] < 1 || $row['start_day'] > 28) {
                throw new InvalidArgumentException('Finance period start day must be between 1 and 28.');
            }

            if ($previous !== null && ($effective <= $previous['effective_on']
                || $effective->day !== $previous['start_day'])) {
                throw new InvalidArgumentException('History must be chronological and changes must start on an existing period boundary.');
            }

            $this->settings[] = ['effective_on' => $effective, 'start_day' => $row['start_day']];
        }
    }

    public function current(): Period
    {
        return $this->forDate(CarbonImmutable::now($this->timezone));
    }

    public function fromKey(string $key): Period
    {
        $period = $this->forDate($this->date($key));

        if ($period->key !== $key) {
            throw new InvalidArgumentException('The key is not a finance period boundary.');
        }

        return $period;
    }

    public function forDate(DateTimeInterface $date): Period
    {
        $date = CarbonImmutable::instance($date)->setTimezone($this->timezone)->startOfDay();
        $index = 0;

        foreach ($this->settings as $candidate => $setting) {
            if ($setting['effective_on'] <= $date) {
                $index = $candidate;
            }
        }

        $setting = $this->settings[$index];
        $start = $date->startOfMonth()->day($setting['start_day']);

        if ($start > $date) {
            $start = $start->subMonthNoOverflow();
        }

        if ($index > 0 && $start < $setting['effective_on']) {
            $start = $setting['effective_on'];
        }

        $next = $start->startOfMonth()->day($setting['start_day']);

        if ($next <= $start) {
            $next = $next->addMonthNoOverflow();
        }

        $change = $this->settings[$index + 1]['effective_on'] ?? null;

        if ($change !== null && $change < $next) {
            $next = $change;
        }

        return new Period($start, $next->subDay()->endOfDay());
    }

    public function previous(Period $period): Period
    {
        return $this->forDate($this->fromKey($period->key)->start->subDay());
    }

    public function next(Period $period): Period
    {
        return $this->forDate($this->fromKey($period->key)->end->addDay());
    }

    private function date(string $date): CarbonImmutable
    {
        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $date, $parts)
            || ! checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            throw new InvalidArgumentException('A valid YYYY-MM-DD date is required.');
        }

        return CarbonImmutable::createFromFormat('!Y-m-d', $date, $this->timezone);
    }
}
