<?php

declare(strict_types=1);

use App\Core\Actions\CreateHousehold;
use App\Core\Enums\MemberRole;
use App\Core\Models\Activity;
use App\Core\Models\Household;
use App\Core\Models\HouseholdMember;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('creates a household with its owner and initial finance period', function (): void {
    $owner = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'test-password']);
    $this->travelTo(Carbon::parse('2026-10-26 10:00:00', 'UTC'));

    $household = app(CreateHousehold::class)->handle($owner, '  Keluarga Uji  ', 25, 'Asia/Jakarta');

    $member = $household->members()->sole();
    app(HouseholdContext::class)->use($household);
    $setting = $household->financePeriodSettings()->sole();

    expect($household->name)->toBe('Keluarga Uji')
        ->and($household->timezone)->toBe('Asia/Jakarta')
        ->and($household->finance_period_start_day)->toBe(25)
        ->and($household->created_by)->toBe($owner->id)
        ->and($member->role)->toBe(MemberRole::Owner)
        ->and($member->slot)->toBe(1)
        ->and($member->color)->toBe(MemberRole::Owner->color())
        ->and($member->user->is($owner))->toBeTrue()
        ->and($setting->start_day)->toBe(25)
        ->and($setting->effective_on->toDateString())->toBe('2026-10-26');

    $log = Activity::query()->sole();
    expect($log->subject->is($household))->toBeTrue()
        ->and($log->causer->is($owner))->toBeTrue()
        ->and($log->description)->toBe('core.household.created');
});

it('rejects a user that already belongs to a household', function (): void {
    $owner = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'test-password']);
    $existing = Household::create(['name' => 'Existing household']);
    $member = new HouseholdMember([
        'role' => MemberRole::Owner,
        'color' => MemberRole::Owner->color(),
        'slot' => 1,
        'joined_at' => now(),
    ]);
    $member->user()->associate($owner);
    $existing->members()->save($member);

    expect(fn () => app(CreateHousehold::class)->handle($owner, 'Second household'))
        ->toThrow(ValidationException::class);

    expect(Household::count())->toBe(1)
        ->and(HouseholdMember::count())->toBe(1)
        ->and(Activity::count())->toBe(0);
});

it('rejects invalid bootstrap input without writing anything', function (string $name, int $startDay, string $timezone): void {
    $owner = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'test-password']);

    expect(fn () => app(CreateHousehold::class)->handle($owner, $name, $startDay, $timezone))
        ->toThrow(ValidationException::class);

    expect(Household::count())->toBe(0)
        ->and(HouseholdMember::count())->toBe(0)
        ->and(Activity::count())->toBe(0);
})->with([
    'blank name' => ['   ', 1, 'Asia/Jakarta'],
    'long name' => [str_repeat('a', 101), 1, 'Asia/Jakarta'],
    'start day 0' => ['Keluarga', 0, 'Asia/Jakarta'],
    'start day 29' => ['Keluarga', 29, 'Asia/Jakarta'],
    'unknown timezone' => ['Keluarga', 1, 'Mars/Olympus'],
]);

it('bootstraps without an ambient tenant context and leaves it unset', function (): void {
    $owner = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'test-password']);

    app(CreateHousehold::class)->handle($owner, 'Keluarga Uji');

    expect(fn () => currentHousehold())->toThrow(LogicException::class);
});

it('never makes a system admin the owner of a household', function (): void {
    $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-password']);
    $admin->is_system_admin = true;
    $admin->save();

    expect(fn () => app(CreateHousehold::class)->handle($admin, 'Keluarga Admin'))
        ->toThrow(ValidationException::class, __('household.admin_not_member'));

    expect(Household::count())->toBe(0)
        ->and(HouseholdMember::count())->toBe(0);
});
