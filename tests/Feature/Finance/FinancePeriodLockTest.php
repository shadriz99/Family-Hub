<?php

declare(strict_types=1);

use App\Core\Actions\CreateHousehold;
use App\Core\Enums\MemberRole;
use App\Core\Models\HouseholdFinancePeriodSetting;
use App\Core\Models\HouseholdMember;
use App\Core\Support\HouseholdContext;
use App\Core\Support\HouseholdScope;
use App\Models\User;
use App\Modules\Finance\Livewire\PeriodSetup;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.finance', true);
});

function makePeriodTestUser(string $email, string $name = 'Anggota'): User
{
    return User::create([
        'name' => $name,
        'email' => $email,
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
    ]);
}

it('allows access to finance routes when an active explicit period covers today', function (): void {
    $owner = makePeriodTestUser('owner1@example.com', 'Owner 1');
    $household = app(CreateHousehold::class)->handle($owner, 'Keluarga Aktif', 1, 'Asia/Jakarta');
    $household->forceFill(['onboarded_at' => now()])->save();

    $this->actingAs($owner)
        ->get(route('finance.index'))
        ->assertOk();

    $this->actingAs($owner)
        ->get('/')
        ->assertOk()
        ->assertDontSee(__('Terkunci'));
});

it('locks finance menu and redirects finance routes to period-setup when previous period has ended', function (): void {
    $owner = makePeriodTestUser('owner2@example.com', 'Owner 2');
    $household = app(CreateHousehold::class)->handle($owner, 'Keluarga Expired', 1, 'Asia/Jakarta');
    $household->forceFill(['onboarded_at' => now()])->save();

    $yesterday = CarbonImmutable::now('Asia/Jakarta')->subDay();
    $lastMonthStart = $yesterday->subDays(29);

    app(HouseholdContext::class)->runInContext($household, function () use ($lastMonthStart, $yesterday): void {
        HouseholdFinancePeriodSetting::query()->delete();
        HouseholdFinancePeriodSetting::create([
            'start_day' => min(28, $lastMonthStart->day),
            'effective_on' => $lastMonthStart->toDateString(),
            'ends_on' => $yesterday->toDateString(),
        ]);
    });

    expect($household->fresh()->hasActiveFinancePeriod())->toBeFalse();

    $this->actingAs($owner)
        ->get(route('finance.index'))
        ->assertRedirect(route('finance.period.setup'));

    $this->actingAs($owner)
        ->get(route('finance.transactions.index'))
        ->assertRedirect(route('finance.period.setup'));

    $this->actingAs($owner)
        ->get('/')
        ->assertOk()
        ->assertSee(__('Terkunci'))
        ->assertSee(route('finance.period.setup'));
});

it('allows both owner and partner to state custom start and end dates to unlock finance', function (): void {
    $owner = makePeriodTestUser('owner3@example.com', 'Owner 3');
    $partner = makePeriodTestUser('partner3@example.com', 'Partner 3');
    $household = app(CreateHousehold::class)->handle($owner, 'Keluarga Pasangan', 1, 'Asia/Jakarta');
    $household->forceFill(['onboarded_at' => now()])->save();

    $partnerMember = new HouseholdMember([
        'role' => MemberRole::Partner,
        'color' => MemberRole::Partner->color(),
        'slot' => 2,
        'joined_at' => now(),
    ]);
    $partnerMember->user()->associate($partner);
    $household->members()->save($partnerMember);

    $yesterday = CarbonImmutable::now('Asia/Jakarta')->subDay();
    $lastMonthStart = $yesterday->subDays(29);

    app(HouseholdContext::class)->runInContext($household, function () use ($lastMonthStart, $yesterday): void {
        HouseholdFinancePeriodSetting::query()->delete();
        HouseholdFinancePeriodSetting::create([
            'start_day' => min(28, $lastMonthStart->day),
            'effective_on' => $lastMonthStart->toDateString(),
            'ends_on' => $yesterday->toDateString(),
        ]);
    });

    $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
    $newEnd = $today->addDays(20);

    $this->actingAs($partner);
    app(HouseholdContext::class)->use($household);

    Livewire::test(PeriodSetup::class)
        ->set('start_date', $today->toDateString())
        ->set('end_date', $newEnd->toDateString())
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('finance.index'));

    $setting = $household->financePeriodSettings()
        ->withoutGlobalScope(HouseholdScope::class)
        ->whereDate('effective_on', $today->toDateString())
        ->first();

    expect($setting)->not->toBeNull()
        ->and($setting->ends_on?->toDateString())->toBe($newEnd->toDateString())
        ->and($household->fresh()->hasActiveFinancePeriod())->toBeTrue();

    $this->actingAs($partner)
        ->get(route('finance.index'))
        ->assertOk();
});

it('rejects end_date earlier than start_date or earlier than today', function (): void {
    $owner = makePeriodTestUser('owner4@example.com', 'Owner 4');
    $household = app(CreateHousehold::class)->handle($owner, 'Keluarga Validasi', 1, 'Asia/Jakarta');
    $household->forceFill(['onboarded_at' => now()])->save();

    $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
    $yesterday = $today->subDay();

    $this->actingAs($owner);
    app(HouseholdContext::class)->use($household);

    Livewire::test(PeriodSetup::class)
        ->set('start_date', $today->toDateString())
        ->set('end_date', $yesterday->toDateString())
        ->call('save')
        ->assertHasErrors(['end_date']);

    Livewire::test(PeriodSetup::class)
        ->set('start_date', $today->subDays(10)->toDateString())
        ->set('end_date', $yesterday->toDateString())
        ->call('save')
        ->assertHasErrors(['end_date']);
});
