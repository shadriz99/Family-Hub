<?php

declare(strict_types=1);

use App\Core\Models\Household;
use App\Core\Models\HouseholdFinancePeriodSetting;
use App\Core\Support\HouseholdContext;
use App\Core\Support\HouseholdScope;
use App\Core\Support\PeriodResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->household = Household::create(['name' => 'Household A', 'timezone' => 'Asia/Jakarta']);
    $this->other = Household::create(['name' => 'Household B', 'timezone' => 'Asia/Jakarta']);
    app(HouseholdContext::class)->use($this->household);
});

afterEach(function (): void {
    app(HouseholdContext::class)->forget();
});

it('fills household_id and isolates tenant reads', function (): void {
    HouseholdFinancePeriodSetting::create(['start_day' => 25, 'effective_on' => '2026-01-01']);

    app(HouseholdContext::class)->use($this->other);
    HouseholdFinancePeriodSetting::create(['start_day' => 1, 'effective_on' => '2026-02-01']);

    expect(HouseholdFinancePeriodSetting::query()->count())->toBe(1)
        ->and(HouseholdFinancePeriodSetting::query()->sole()->household->is($this->other))->toBeTrue();

    app(HouseholdContext::class)->use($this->household);

    expect(HouseholdFinancePeriodSetting::query()->count())->toBe(1)
        ->and(HouseholdFinancePeriodSetting::query()->sole()->household->is($this->household))->toBeTrue()
        ->and(HouseholdFinancePeriodSetting::withoutGlobalScope(HouseholdScope::class)->count())->toBe(2);
});

it('fails closed without a household context', function (): void {
    app(HouseholdContext::class)->forget();

    expect(fn () => HouseholdFinancePeriodSetting::query()->get())->toThrow(LogicException::class)
        ->and(fn () => currentHousehold())->toThrow(LogicException::class)
        ->and(fn () => HouseholdFinancePeriodSetting::create(['start_day' => 1, 'effective_on' => '2026-01-01']))
        ->toThrow(LogicException::class);

    expect(HouseholdFinancePeriodSetting::withoutGlobalScope(HouseholdScope::class)->count())->toBe(0);
});

it('rejects writes for another household', function (): void {
    expect(fn () => HouseholdFinancePeriodSetting::forceCreate([
        'household_id' => $this->other->id,
        'start_day' => 1,
        'effective_on' => '2026-01-01',
    ]))->toThrow(LogicException::class);

    expect(HouseholdFinancePeriodSetting::withoutGlobalScope(HouseholdScope::class)->count())->toBe(0);
});

it('builds a resolver from persisted household history without ambient context', function (): void {
    $this->household->financePeriodSettings()->create(['start_day' => 25, 'effective_on' => '2026-01-01']);
    $this->household->financePeriodSettings()->create(['start_day' => 1, 'effective_on' => '2026-11-25']);

    app(HouseholdContext::class)->use($this->other);

    $resolver = PeriodResolver::for($this->household);

    expect($resolver->fromKey('2026-10-25')->end->toDateString())->toBe('2026-11-24')
        ->and($resolver->fromKey('2026-11-25')->end->toDateString())->toBe('2026-11-30')
        ->and($resolver->fromKey('2026-12-01')->end->toDateString())->toBe('2026-12-31');
});

it('uses the household timezone and rejects empty history', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-31 18:00:00', 'UTC'));

    try {
        $jakarta = Household::create(['name' => 'Jakarta', 'timezone' => 'Asia/Jakarta']);
        app(HouseholdContext::class)->use($jakarta);
        $jakarta->financePeriodSettings()->create(['start_day' => 1, 'effective_on' => '2026-01-01']);

        expect(PeriodResolver::for($jakarta)->current()->key)->toBe('2026-11-01');

        $empty = Household::create(['name' => 'Empty household']);
        expect(fn () => PeriodResolver::for($empty))->toThrow(InvalidArgumentException::class);
    } finally {
        CarbonImmutable::setTestNow();
    }
});
