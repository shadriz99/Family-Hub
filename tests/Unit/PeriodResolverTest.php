<?php

declare(strict_types=1);

use App\Core\Support\PeriodResolver;
use Carbon\CarbonImmutable;

it('resolves calendar, salary, year-end and leap-year periods', function (
    int $day,
    string $date,
    string $start,
    string $end,
): void {
    $resolver = new PeriodResolver([['effective_on' => '2020-01-01', 'start_day' => $day]], 'Asia/Jakarta');
    $period = $resolver->forDate(CarbonImmutable::parse($date, 'Asia/Jakarta'));

    expect($period->key)->toBe($start)
        ->and($period->start->toDateString())->toBe($start)
        ->and($period->end->toDateString())->toBe($end)
        ->and($resolver->next($resolver->previous($period))->key)->toBe($start);
})->with([
    [1, '2026-11-12', '2026-11-01', '2026-11-30'],
    [25, '2026-11-24', '2026-10-25', '2026-11-24'],
    [25, '2026-11-25', '2026-11-25', '2026-12-24'],
    [25, '2027-01-01', '2026-12-25', '2027-01-24'],
    [1, '2024-02-29', '2024-02-01', '2024-02-29'],
    [28, '2024-02-29', '2024-02-28', '2024-03-27'],
    [1, '2019-12-31', '2019-12-01', '2019-12-31'],
]);

it('preserves history through transitions and multiple changes', function (): void {
    $resolver = new PeriodResolver([
        ['effective_on' => '2026-01-01', 'start_day' => 25],
        ['effective_on' => '2026-11-25', 'start_day' => 1],
        ['effective_on' => '2027-01-01', 'start_day' => 25],
    ], 'Asia/Jakarta');

    expect($resolver->fromKey('2026-10-25')->end->toDateString())->toBe('2026-11-24')
        ->and($resolver->fromKey('2026-11-25')->end->toDateString())->toBe('2026-11-30')
        ->and($resolver->next($resolver->fromKey('2026-11-25'))->key)->toBe('2026-12-01')
        ->and($resolver->fromKey('2027-01-01')->end->toDateString())->toBe('2027-01-24')
        ->and($resolver->next($resolver->fromKey('2027-01-01'))->key)->toBe('2027-01-25')
        ->and($resolver->previous($resolver->fromKey('2027-01-25'))->key)->toBe('2027-01-01');

    expect(fn () => $resolver->fromKey('2026-12-25'))->toThrow(InvalidArgumentException::class);
});

it('uses the household timezone without modifying the supplied date', function (): void {
    $resolver = new PeriodResolver([['effective_on' => '2026-01-01', 'start_day' => 1]], 'Asia/Jakarta');
    $utc = CarbonImmutable::parse('2026-10-31 18:00:00', 'UTC');

    expect($resolver->forDate($utc)->key)->toBe('2026-11-01')
        ->and($utc->timezoneName)->toBe('UTC');
});

it('resolves the current period with a frozen household clock', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-31 18:00:00', 'UTC'));

    try {
        $resolver = new PeriodResolver([['effective_on' => '2026-01-01', 'start_day' => 1]], 'Asia/Jakarta');
        expect($resolver->current()->key)->toBe('2026-11-01');
    } finally {
        CarbonImmutable::setTestNow();
    }
});

it('handles a longer transition than the preceding February period', function (): void {
    $resolver = new PeriodResolver([
        ['effective_on' => '2026-01-01', 'start_day' => 25],
        ['effective_on' => '2026-03-25', 'start_day' => 24],
    ], 'Asia/Jakarta');

    $old = $resolver->fromKey('2026-02-25');
    $transition = $resolver->fromKey('2026-03-25');

    expect($old->end->toDateString())->toBe('2026-03-24')
        ->and($transition->end->toDateString())->toBe('2026-04-23')
        ->and($resolver->next($transition)->key)->toBe('2026-04-24')
        ->and($transition->start->diffInDays($transition->end->addMicrosecond()))->toBe(30.0);
});

it('keeps daily coverage contiguous across history changes', function (): void {
    $resolver = new PeriodResolver([
        ['effective_on' => '2026-01-01', 'start_day' => 25],
        ['effective_on' => '2026-03-25', 'start_day' => 1],
        ['effective_on' => '2026-07-01', 'start_day' => 28],
    ], 'Asia/Jakarta');

    for ($day = 0; $day < 400; $day++) {
        $date = CarbonImmutable::parse('2025-12-01', 'Asia/Jakarta')->addDays($day);
        $period = $resolver->forDate($date);
        expect($date->betweenIncluded($period->start, $period->end))->toBeTrue()
            ->and($resolver->next($period)->start->equalTo($period->end->addMicrosecond()))->toBeTrue()
            ->and($resolver->previous($resolver->next($period))->key)->toBe($period->key);
    }
});

it('rejects invalid history and invalid period keys', function (): void {
    expect(fn () => new PeriodResolver([], 'UTC'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new PeriodResolver([['effective_on' => '2026-02-30', 'start_day' => 1]], 'UTC'))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => new PeriodResolver([['effective_on' => '2026-01-01', 'start_day' => 29]], 'UTC'))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => new PeriodResolver([
            ['effective_on' => '2026-01-01', 'start_day' => 25],
            ['effective_on' => '2026-02-01', 'start_day' => 1],
        ], 'UTC'))->toThrow(InvalidArgumentException::class);

    $resolver = new PeriodResolver([['effective_on' => '2026-01-01', 'start_day' => 1]], 'UTC');
    expect(fn () => $resolver->fromKey('2026-11-02'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $resolver->fromKey('2026-2-01'))->toThrow(InvalidArgumentException::class);
});

it('rejects duplicate or reversed history instead of silently reordering it', function (): void {
    foreach (['2026-01-01', '2025-01-01'] as $date) {
        expect(fn () => new PeriodResolver([
            ['effective_on' => '2026-01-01', 'start_day' => 1],
            ['effective_on' => $date, 'start_day' => 25],
        ], 'UTC'))->toThrow(InvalidArgumentException::class);
    }
});
