<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Blade;

it('renders exact Rupiah with optional negative coloring and caller attributes', function (): void {
    $plain = Blade::render('<x-money :amount="-50000" data-testid="amount" />');
    $colored = Blade::render('<x-money :amount="-50000" colored class="font-bold" />');
    $positive = Blade::render('<x-money :amount="1250000" colored />');

    expect($plain)->toContain('-Rp 50.000', 'data-testid="amount"', 'tabular-nums')
        ->not->toContain('text-[#ad3f45]')
        ->and($colored)->toContain('-Rp 50.000', 'text-[#ad3f45]', 'dark:text-[#ffafb1]', 'font-bold')
        ->and($positive)->toContain('Rp 1.250.000')->not->toContain('text-[#ad3f45]');
});

it('renders a local calendar date without shifting it across timezones', function (): void {
    app()->setLocale('id');

    expect(Blade::render('<x-date value="2026-10-27" timezone="Pacific/Honolulu" class="text-sm" />'))
        ->toContain('datetime="2026-10-27"', '27 Okt 2026', 'class="text-sm"');
});

it('converts an instant to the supplied household timezone without mutating it', function (): void {
    app()->setLocale('id');
    $value = CarbonImmutable::parse('2026-10-31 18:00:00', 'UTC');

    expect(Blade::render('<x-date :value="$value" timezone="Asia/Jakarta" />', ['value' => $value]))
        ->toContain('1 Nov 2026', 'datetime="2026-11-01T01:00:00+07:00"')
        ->and($value->timezoneName)->toBe('UTC');
});

it('supports English date localization', function (): void {
    app()->setLocale('en');

    expect(Blade::render('<x-date value="2026-10-27" />'))->toContain('27 Oct 2026');
});

it('rejects invalid dates instead of normalizing or defaulting them', function (string $value): void {
    expect(fn () => new App\View\Components\Date($value))->toThrow(InvalidArgumentException::class);
})->with(['2026-02-30', '2026-2-01', 'today', '', '2026-10-27T12:00:00Z']);
