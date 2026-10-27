<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Recurrence\Recurrence;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Tests\TestCase;

class RecurrenceTest extends TestCase
{
    public function test_it_generates_daily_occurrences(): void
    {
        $dtstart = '2026-01-01 08:00:00';
        $rrule = 'FREQ=DAILY;INTERVAL=1';

        $occurrences = Recurrence::occurrencesBetween(
            $rrule,
            $dtstart,
            '2026-01-01 00:00:00',
            '2026-01-05 23:59:59'
        );

        $this->assertCount(5, $occurrences);
        $this->assertSame('2026-01-01 08:00:00', $occurrences[0]->toDateTimeString());
        $this->assertSame('2026-01-05 08:00:00', $occurrences[4]->toDateTimeString());
    }

    public function test_it_generates_daily_occurrences_with_interval_two(): void
    {
        $dtstart = '2026-01-01 09:00:00';
        $rrule = 'FREQ=DAILY;INTERVAL=2';

        $occurrences = Recurrence::occurrencesBetween(
            $rrule,
            $dtstart,
            '2026-01-01 00:00:00',
            '2026-01-07 23:59:59'
        );

        $this->assertCount(4, $occurrences);
        $this->assertSame('2026-01-01 09:00:00', $occurrences[0]->toDateTimeString());
        $this->assertSame('2026-01-03 09:00:00', $occurrences[1]->toDateTimeString());
        $this->assertSame('2026-01-05 09:00:00', $occurrences[2]->toDateTimeString());
        $this->assertSame('2026-01-07 09:00:00', $occurrences[3]->toDateTimeString());
    }

    public function test_it_generates_weekly_occurrences_on_specific_days(): void
    {
        // 2026-01-05 is a Monday
        $dtstart = '2026-01-05 10:00:00';
        $rrule = 'FREQ=WEEKLY;BYDAY=MO,WE,FR';

        $occurrences = Recurrence::occurrencesBetween(
            $rrule,
            $dtstart,
            '2026-01-05 00:00:00',
            '2026-01-11 23:59:59'
        );

        $this->assertCount(3, $occurrences);
        $this->assertSame('2026-01-05 10:00:00', $occurrences[0]->toDateTimeString()); // Mon
        $this->assertSame('2026-01-07 10:00:00', $occurrences[1]->toDateTimeString()); // Wed
        $this->assertSame('2026-01-09 10:00:00', $occurrences[2]->toDateTimeString()); // Fri
    }

    public function test_it_handles_monthly_by_monthday(): void
    {
        $dtstart = '2026-01-15 00:00:00';
        $rrule = 'FREQ=MONTHLY;BYMONTHDAY=15';

        $occurrences = Recurrence::occurrencesBetween(
            $rrule,
            $dtstart,
            '2026-01-01 00:00:00',
            '2026-04-30 23:59:59'
        );

        $this->assertCount(4, $occurrences);
        $this->assertSame('2026-01-15 00:00:00', $occurrences[0]->toDateTimeString());
        $this->assertSame('2026-02-15 00:00:00', $occurrences[1]->toDateTimeString());
        $this->assertSame('2026-03-15 00:00:00', $occurrences[2]->toDateTimeString());
        $this->assertSame('2026-04-15 00:00:00', $occurrences[3]->toDateTimeString());
    }

    public function test_it_handles_end_of_month_preset_and_leap_years(): void
    {
        // Leap year 2028: Feb has 29 days
        $dtstart = '2028-01-31 12:00:00';
        $rrule = 'FREQ=MONTHLY;BYMONTHDAY=-1';

        $occurrences = Recurrence::occurrencesBetween(
            $rrule,
            $dtstart,
            '2028-01-01 00:00:00',
            '2028-04-30 23:59:59'
        );

        $this->assertCount(4, $occurrences);
        $this->assertSame('2028-01-31 12:00:00', $occurrences[0]->toDateTimeString());
        $this->assertSame('2028-02-29 12:00:00', $occurrences[1]->toDateTimeString());
        $this->assertSame('2028-03-31 12:00:00', $occurrences[2]->toDateTimeString());
        $this->assertSame('2028-04-30 12:00:00', $occurrences[3]->toDateTimeString());

        // Non-leap year 2026: Feb has 28 days
        $dtstart2026 = '2026-01-31 12:00:00';
        $occurrences2026 = Recurrence::occurrencesBetween(
            $rrule,
            $dtstart2026,
            '2026-02-01 00:00:00',
            '2026-02-28 23:59:59'
        );
        $this->assertCount(1, $occurrences2026);
        $this->assertSame('2026-02-28 12:00:00', $occurrences2026[0]->toDateTimeString());
    }

    public function test_it_handles_count_limit(): void
    {
        $dtstart = '2026-01-01 08:00:00';
        $rrule = 'FREQ=DAILY;COUNT=3';

        $occurrences = Recurrence::occurrencesBetween(
            $rrule,
            $dtstart,
            '2026-01-01 00:00:00',
            '2026-01-31 23:59:59'
        );

        $this->assertCount(3, $occurrences);
        $this->assertSame('2026-01-03 08:00:00', $occurrences[2]->toDateTimeString());
    }

    public function test_it_handles_until_limit(): void
    {
        $dtstart = '2026-01-01 08:00:00';
        $rrule = 'FREQ=DAILY;UNTIL=20260103T235959Z';

        $occurrences = Recurrence::occurrencesBetween(
            $rrule,
            $dtstart,
            '2026-01-01 00:00:00',
            '2026-01-10 23:59:59'
        );

        $this->assertCount(3, $occurrences);
        $this->assertSame('2026-01-03 08:00:00', $occurrences[2]->toDateTimeString());
    }

    public function test_it_throws_exception_when_from_is_after_to(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Start of range ($from) cannot be after end of range ($to).');

        Recurrence::occurrencesBetween(
            'FREQ=DAILY',
            '2026-01-01',
            '2026-01-10',
            '2026-01-05'
        );
    }

    public function test_next_returns_upcoming_occurrence(): void
    {
        $dtstart = '2026-01-01 08:00:00';
        $rrule = 'FREQ=DAILY';

        // After strictly
        $next = Recurrence::next($rrule, $dtstart, '2026-01-01 08:00:00', inclusive: false);
        $this->assertInstanceOf(CarbonImmutable::class, $next);
        $this->assertSame('2026-01-02 08:00:00', $next->toDateTimeString());

        // After inclusive
        $nextInclusive = Recurrence::next($rrule, $dtstart, '2026-01-01 08:00:00', inclusive: true);
        $this->assertInstanceOf(CarbonImmutable::class, $nextInclusive);
        $this->assertSame('2026-01-01 08:00:00', $nextInclusive->toDateTimeString());
    }

    public function test_next_returns_null_when_exhausted(): void
    {
        $dtstart = '2026-01-01 08:00:00';
        $rrule = 'FREQ=DAILY;COUNT=2';

        $next = Recurrence::next($rrule, $dtstart, '2026-01-05 08:00:00');
        $this->assertNull($next);
    }

    public function test_humanize_indonesian(): void
    {
        $this->assertSame('Setiap hari', Recurrence::humanize('FREQ=DAILY'));
        $this->assertSame('Setiap 3 hari', Recurrence::humanize('FREQ=DAILY;INTERVAL=3'));

        $this->assertSame('Setiap minggu pada hari Senin', Recurrence::humanize('FREQ=WEEKLY;BYDAY=MO'));
        $this->assertSame('Setiap 2 minggu pada hari Senin, Rabu', Recurrence::humanize('FREQ=WEEKLY;INTERVAL=2;BYDAY=MO,WE'));

        $this->assertSame('Setiap bulan tanggal 15', Recurrence::humanize('FREQ=MONTHLY;BYMONTHDAY=15'));
        $this->assertSame('Setiap bulan (akhir bulan)', Recurrence::humanize('FREQ=MONTHLY;BYMONTHDAY=-1'));
        $this->assertSame('Setiap 3 bulan tanggal 1', Recurrence::humanize('FREQ=MONTHLY;INTERVAL=3;BYMONTHDAY=1'));

        $this->assertSame('Setiap tahun', Recurrence::humanize('FREQ=YEARLY'));
        $this->assertSame('Setiap 2 tahun', Recurrence::humanize('FREQ=YEARLY;INTERVAL=2'));

        $this->assertSame('Setiap hari sebanyak 5 kali', Recurrence::humanize('FREQ=DAILY;COUNT=5'));
        $this->assertSame('Setiap hari sampai 31 Des 2026', Recurrence::humanize('FREQ=DAILY;UNTIL=20261231T235959Z'));
    }

    public function test_humanize_english(): void
    {
        $this->assertSame('Every day', Recurrence::humanize('FREQ=DAILY', locale: 'en'));
        $this->assertSame('Every 3 days', Recurrence::humanize('FREQ=DAILY;INTERVAL=3', locale: 'en'));

        $this->assertSame('Every week on Monday', Recurrence::humanize('FREQ=WEEKLY;BYDAY=MO', locale: 'en'));
        $this->assertSame('Every 2 weeks on Monday, Wednesday', Recurrence::humanize('FREQ=WEEKLY;INTERVAL=2;BYDAY=MO,WE', locale: 'en'));

        $this->assertSame('Every month on day 15', Recurrence::humanize('FREQ=MONTHLY;BYMONTHDAY=15', locale: 'en'));
        $this->assertSame('Every month (end of month)', Recurrence::humanize('FREQ=MONTHLY;BYMONTHDAY=-1', locale: 'en'));

        $this->assertSame('Every year', Recurrence::humanize('FREQ=YEARLY', locale: 'en'));
        $this->assertSame('Every 2 years', Recurrence::humanize('FREQ=YEARLY;INTERVAL=2', locale: 'en'));

        $this->assertSame('Every day 5 times', Recurrence::humanize('FREQ=DAILY;COUNT=5', locale: 'en'));
        $this->assertSame('Every day until 31 Dec 2026', Recurrence::humanize('FREQ=DAILY;UNTIL=20261231T235959Z', locale: 'en'));
    }
}
