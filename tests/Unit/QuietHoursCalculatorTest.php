<?php

declare(strict_types=1);

use App\Core\Services\QuietHoursCalculator;
use Carbon\CarbonImmutable;

it('leaves target time unchanged when quiet hours are missing, invalid, or zero duration', function (): void {
    $calculator = new QuietHoursCalculator();
    $target = CarbonImmutable::parse('2026-10-27 15:30:00', 'UTC');

    expect($calculator->calculateEffectiveTime($target, null, null)->toIso8601String())
        ->toBe('2026-10-27T15:30:00+00:00')
        ->and($calculator->calculateEffectiveTime($target, '', '06:00')->toIso8601String())
        ->toBe('2026-10-27T15:30:00+00:00')
        ->and($calculator->calculateEffectiveTime($target, '21:00', '21:00')->toIso8601String())
        ->toBe('2026-10-27T15:30:00+00:00');
});

it('shifts evening cross-midnight reminder to next morning end of quiet hours', function (): void {
    $calculator = new QuietHoursCalculator();
    // 22:30 Asia/Jakarta (UTC+7) is 15:30 UTC
    $targetUtc = CarbonImmutable::parse('2026-10-27 15:30:00', 'UTC');

    $effective = $calculator->calculateEffectiveTime($targetUtc, '21:00', '06:00', 'Asia/Jakarta');

    // Expected: 2026-10-28 06:00:00 Asia/Jakarta = 2026-10-27 23:00:00 UTC
    expect($effective->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'))
        ->toBe('2026-10-28 06:00:00')
        ->and($effective->format('Y-m-d H:i:s'))
        ->toBe('2026-10-27 23:00:00');
});

it('shifts early morning cross-midnight reminder to same morning end of quiet hours', function (): void {
    $calculator = new QuietHoursCalculator();
    // 03:15 Asia/Jakarta (UTC+7) is 2026-10-26 20:15 UTC
    $targetUtc = CarbonImmutable::parse('2026-10-26 20:15:00', 'UTC');

    $effective = $calculator->calculateEffectiveTime($targetUtc, '21:00', '06:00', 'Asia/Jakarta');

    // Expected: 2026-10-27 06:00:00 Asia/Jakarta = 2026-10-26 23:00:00 UTC
    expect($effective->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'))
        ->toBe('2026-10-27 06:00:00')
        ->and($effective->format('Y-m-d H:i:s'))
        ->toBe('2026-10-26 23:00:00');
});

it('leaves daytime reminder outside quiet hours intact', function (): void {
    $calculator = new QuietHoursCalculator();
    // 14:00 Asia/Jakarta = 07:00 UTC
    $targetUtc = CarbonImmutable::parse('2026-10-27 07:00:00', 'UTC');

    $effective = $calculator->calculateEffectiveTime($targetUtc, '21:00', '06:00', 'Asia/Jakarta');

    expect($effective->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'))
        ->toBe('2026-10-27 14:00:00');
});

it('handles exact boundaries: shifts at start but opens at end', function (): void {
    $calculator = new QuietHoursCalculator();

    // Exactly 21:00 Asia/Jakarta = 14:00 UTC
    $atStartUtc = CarbonImmutable::parse('2026-10-27 14:00:00', 'UTC');
    $effectiveStart = $calculator->calculateEffectiveTime($atStartUtc, '21:00', '06:00', 'Asia/Jakarta');
    expect($effectiveStart->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'))
        ->toBe('2026-10-28 06:00:00');

    // Exactly 06:00 Asia/Jakarta = 23:00 UTC previous day
    $atEndUtc = CarbonImmutable::parse('2026-10-26 23:00:00', 'UTC');
    $effectiveEnd = $calculator->calculateEffectiveTime($atEndUtc, '21:00', '06:00', 'Asia/Jakarta');
    expect($effectiveEnd->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'))
        ->toBe('2026-10-27 06:00:00');
});

it('shifts afternoon same-day quiet hours correctly', function (): void {
    $calculator = new QuietHoursCalculator();
    // 13:30 Asia/Jakarta = 06:30 UTC
    $targetUtc = CarbonImmutable::parse('2026-10-27 06:30:00', 'UTC');

    $effective = $calculator->calculateEffectiveTime($targetUtc, '13:00', '14:00', 'Asia/Jakarta');

    expect($effective->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'))
        ->toBe('2026-10-27 14:00:00');
});
