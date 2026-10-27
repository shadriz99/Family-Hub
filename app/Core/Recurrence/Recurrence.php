<?php

declare(strict_types=1);

namespace App\Core\Recurrence;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use RRule\RRule;

final class Recurrence
{
    /** Maximum occurrences returned in a single query to protect server resources. */
    public const MAX_OCCURRENCES = 500;

    /**
     * Resolve all occurrences between $from and $to (inclusive).
     *
     * @return array<CarbonImmutable>
     */
    public static function occurrencesBetween(
        string $rrule,
        DateTimeInterface|string $dtstart,
        DateTimeInterface|string $from,
        DateTimeInterface|string $to,
        int $limit = self::MAX_OCCURRENCES
    ): array {
        $startDate = self::toCarbon($dtstart);
        $fromDate = self::toCarbon($from);
        $toDate = self::toCarbon($to);

        if ($fromDate->greaterThan($toDate)) {
            throw new InvalidArgumentException('Start of range ($from) cannot be after end of range ($to).');
        }

        $rule = self::createRRule($rrule, $startDate);

        $results = [];
        $occurrences = $rule->getOccurrencesBetween(
            $fromDate->toDateTimeString(),
            $toDate->toDateTimeString(),
            $limit
        );

        foreach ($occurrences as $occurrence) {
            $results[] = CarbonImmutable::instance($occurrence);
        }

        return $results;
    }

    /**
     * Get the next occurrence strictly after (or optionally on or after) the given date.
     */
    public static function next(
        string $rrule,
        DateTimeInterface|string $dtstart,
        DateTimeInterface|string $after,
        bool $inclusive = false
    ): ?CarbonImmutable {
        $startDate = self::toCarbon($dtstart);
        $afterDate = self::toCarbon($after);

        $rule = self::createRRule($rrule, $startDate);
        $occurrences = $rule->getOccurrencesAfter($afterDate->toDateTimeString(), $inclusive, 1);

        return ! empty($occurrences) ? CarbonImmutable::instance($occurrences[0]) : null;
    }

    /**
     * Convert an RRULE string into a friendly, human-readable description in Indonesian or English.
     */
    public static function humanize(string $rrule, string $locale = 'id'): string
    {
        $parts = self::parseRRuleString($rrule);
        $freq = strtoupper($parts['FREQ'] ?? 'DAILY');
        $interval = isset($parts['INTERVAL']) ? max(1, (int) $parts['INTERVAL']) : 1;
        $byMonthDay = isset($parts['BYMONTHDAY']) ? (int) $parts['BYMONTHDAY'] : null;
        $byDay = isset($parts['BYDAY']) ? explode(',', strtoupper($parts['BYDAY'])) : [];
        $count = isset($parts['COUNT']) ? (int) $parts['COUNT'] : null;
        $until = isset($parts['UNTIL']) ? $parts['UNTIL'] : null;

        $isId = str_starts_with($locale, 'id');

        $desc = match ($freq) {
            'DAILY' => self::humanizeDaily($interval, $isId),
            'WEEKLY' => self::humanizeWeekly($interval, $byDay, $isId),
            'MONTHLY' => self::humanizeMonthly($interval, $byMonthDay, $byDay, $isId),
            'YEARLY' => self::humanizeYearly($interval, $isId),
            default => $isId ? 'Berulang' : 'Recurring',
        };

        if ($count !== null) {
            $desc .= $isId ? " sebanyak {$count} kali" : " {$count} times";
        } elseif ($until !== null) {
            $untilDate = self::parseUntilDate($until);
            if ($untilDate) {
                $formatted = $untilDate->locale($locale)->translatedFormat('j M Y');
                $desc .= $isId ? " sampai {$formatted}" : " until {$formatted}";
            }
        }

        return $desc;
    }

    private static function humanizeDaily(int $interval, bool $isId): string
    {
        if ($interval === 1) {
            return $isId ? 'Setiap hari' : 'Every day';
        }

        return $isId ? "Setiap {$interval} hari" : "Every {$interval} days";
    }

    /**
     * @param array<string> $byDay
     */
    private static function humanizeWeekly(int $interval, array $byDay, bool $isId): string
    {
        $dayNames = [
            'MO' => $isId ? 'Senin' : 'Monday',
            'TU' => $isId ? 'Selasa' : 'Tuesday',
            'WE' => $isId ? 'Rabu' : 'Wednesday',
            'TH' => $isId ? 'Kamis' : 'Thursday',
            'FR' => $isId ? 'Jumat' : 'Friday',
            'SA' => $isId ? 'Sabtu' : 'Saturday',
            'SU' => $isId ? 'Minggu' : 'Sunday',
        ];

        $namedDays = array_map(fn (string $d) => $dayNames[$d] ?? $d, $byDay);
        $daysStr = implode(', ', $namedDays);

        if ($interval === 1) {
            $base = $isId ? 'Setiap minggu' : 'Every week';
        } else {
            $base = $isId ? "Setiap {$interval} minggu" : "Every {$interval} weeks";
        }

        if ($daysStr !== '') {
            return $isId ? "{$base} pada hari {$daysStr}" : "{$base} on {$daysStr}";
        }

        return $base;
    }

    /**
     * @param array<string> $byDay
     */
    private static function humanizeMonthly(int $interval, ?int $byMonthDay, array $byDay, bool $isId): string
    {
        if ($interval === 1) {
            $base = $isId ? 'Setiap bulan' : 'Every month';
        } else {
            $base = $isId ? "Setiap {$interval} bulan" : "Every {$interval} months";
        }

        if ($byMonthDay === -1) {
            return $isId ? "{$base} (akhir bulan)" : "{$base} (end of month)";
        }

        if ($byMonthDay !== null) {
            return $isId ? "{$base} tanggal {$byMonthDay}" : "{$base} on day {$byMonthDay}";
        }

        return $base;
    }

    private static function humanizeYearly(int $interval, bool $isId): string
    {
        if ($interval === 1) {
            return $isId ? 'Setiap tahun' : 'Every year';
        }

        return $isId ? "Setiap {$interval} tahun" : "Every {$interval} years";
    }

    private static function toCarbon(DateTimeInterface|string $date): CarbonImmutable
    {
        if ($date instanceof CarbonImmutable) {
            return $date;
        }

        if ($date instanceof DateTimeInterface) {
            return CarbonImmutable::instance($date);
        }

        return CarbonImmutable::parse($date);
    }

    /**
     * @return RRule<\DateTimeInterface>
     */
    private static function createRRule(string $rrule, CarbonImmutable $dtstart): RRule
    {
        $parts = self::parseRRuleString($rrule);
        $parts['DTSTART'] = $dtstart->toDateTimeString();

        return new RRule($parts);
    }

    /**
     * @return array<string, string>
     */
    private static function parseRRuleString(string $rrule): array
    {
        $rrule = trim($rrule);
        if (str_starts_with($rrule, 'RRULE:')) {
            $rrule = substr($rrule, 6);
        }

        $parts = [];
        foreach (explode(';', $rrule) as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }
            $pair = explode('=', $chunk, 2);
            if (count($pair) === 2) {
                $parts[strtoupper(trim($pair[0]))] = trim($pair[1]);
            }
        }

        return $parts;
    }

    private static function parseUntilDate(string $until): ?CarbonImmutable
    {
        // Format YYYYMMDD or YYYYMMDDTHHMMSSZ
        if (preg_match('/^(\d{4})(\d{2})(\d{2})/', $until, $m)) {
            return CarbonImmutable::createFromDate((int) $m[1], (int) $m[2], (int) $m[3]);
        }

        return null;
    }
}
