<?php

declare(strict_types=1);

namespace App\Core\Support;

use App\Core\Models\Household;
use App\Core\Models\HouseholdFinancePeriodSetting;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

final class PeriodResolver
{
    /** @var list<array{effective_on: CarbonImmutable, start_day: int, ends_on: CarbonImmutable|null}> */
    private array $settings = [];

    /**
     * @param list<array{effective_on: string, start_day: int, ends_on?: string|null}> $history
     */
    public function __construct(array $history, private readonly string $timezone)
    {
        new DateTimeZone($timezone);

        if ($history === []) {
            throw new InvalidArgumentException('Finance period history is required.');
        }

        foreach ($history as $row) {
            $effective = $this->date($row['effective_on']);
            $endsOn = isset($row['ends_on']) && $row['ends_on'] !== null ? $this->date($row['ends_on']) : null;
            $previous = $this->settings === [] ? null : $this->settings[array_key_last($this->settings)];

            if ($row['start_day'] < 1 || $row['start_day'] > 28) {
                throw new InvalidArgumentException('Finance period start day must be between 1 and 28.');
            }

            if ($endsOn !== null && $endsOn < $effective) {
                throw new InvalidArgumentException('Finance period end date must not precede start date.');
            }

            if ($previous !== null && $effective <= $previous['effective_on']) {
                throw new InvalidArgumentException('History must be chronological.');
            }

            if ($previous !== null && $previous['ends_on'] === null && $endsOn === null
                && $effective->day !== $previous['start_day']) {
                throw new InvalidArgumentException('History must be chronological and changes must start on an existing period boundary.');
            }

            $this->settings[] = [
                'effective_on' => $effective,
                'start_day' => $row['start_day'],
                'ends_on' => $endsOn,
            ];
        }
    }

    public function current(): Period
    {
        return $this->forDate(CarbonImmutable::now($this->timezone));
    }

    /**
     * Builds a resolver from the household's persisted period history and timezone.
     * The explicit household is the tenant boundary, so this also works in jobs
     * without an ambient household context.
     */
    public static function for(Household $household): self
    {
        $history = $household->financePeriodSettings()
            ->withoutGlobalScope(HouseholdScope::class)
            ->get()
            ->map(static fn (HouseholdFinancePeriodSetting $setting): array => [
                'effective_on' => $setting->effective_on->toDateString(),
                'start_day' => $setting->start_day,
                'ends_on' => $setting->ends_on?->toDateString(),
            ])
            ->values()
            ->all();

        return new self($history, $household->timezone);
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

        // Check explicit start/end period definitions first.
        foreach ($this->settings as $setting) {
            if ($setting['ends_on'] !== null
                && $setting['effective_on'] <= $date
                && $setting['ends_on'] >= $date) {
                return new Period($setting['effective_on'], $setting['ends_on']->endOfDay());
            }
        }

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
