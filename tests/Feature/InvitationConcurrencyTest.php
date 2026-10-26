<?php

declare(strict_types=1);

use App\Core\Actions\AcceptInvitation;
use App\Core\Actions\CreateHousehold;
use App\Core\Actions\InviteHouseholdMember;
use App\Core\Enums\MemberRole;
use App\Core\Models\Activity;
use App\Core\Models\Household;
use App\Core\Models\HouseholdInvitation;
use App\Core\Models\HouseholdMember;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->owner = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'test-password']);
    $this->guestUser = User::create(['name' => 'Guest', 'email' => 'guest@example.test', 'password' => 'test-password']);
    $this->household = app(CreateHousehold::class)->handle($this->owner, 'Keluarga Uji');
    $this->travelTo(Carbon::parse('2026-10-26 10:00:00', 'UTC'));
});

/**
 * Insert a member row directly, bypassing the models and actions, so the test exercises
 * the database constraint instead of the application-level checks.
 */
function insertMemberRow(Household $household, User $user, int $slot): void
{
    DB::table('household_members')->insert([
        'id' => (string) Str::ulid(),
        'household_id' => $household->getKey(),
        'user_id' => $user->getKey(),
        'role' => MemberRole::Partner->value,
        'color' => MemberRole::Partner->color(),
        'joined_at' => now(),
        'slot' => $slot,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('fails closed when a competing acceptance takes the last slot mid-flight', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'guest@example.test');
    $rival = User::create(['name' => 'Rival', 'email' => 'rival@example.test', 'password' => 'test-password']);

    // Reproduce the interleaving deterministically: another request commits the only free
    // slot after this acceptance read the member list but before it writes its own row.
    $competed = false;
    HouseholdMember::creating(function () use (&$competed, $rival): void {
        if ($competed) {
            return;
        }

        $competed = true;
        insertMemberRow($this->household, $rival, 2);
    });

    expect(fn () => app(AcceptInvitation::class)->handle($this->guestUser, $issued->token))
        ->toThrow(ValidationException::class, __('household.invite_full'));

    expect($competed)->toBeTrue()
        ->and($this->household->members()->count())->toBe(1)
        ->and($this->guestUser->householdMembership()->exists())->toBeFalse()
        ->and(HouseholdInvitation::query()->sole()->accepted_at)->toBeNull()
        ->and(Activity::query()->where('description', 'core.household.partner_joined')->exists())->toBeFalse();
});

it('rejects a third member at the database level even without the application guard', function (): void {
    $partner = User::create(['name' => 'Partner', 'email' => 'partner@example.test', 'password' => 'test-password']);
    $third = User::create(['name' => 'Third', 'email' => 'third@example.test', 'password' => 'test-password']);

    insertMemberRow($this->household, $partner, 2);

    expect(fn () => insertMemberRow($this->household, $third, 2))
        ->toThrow(UniqueConstraintViolationException::class);

    expect($this->household->members()->count())->toBe(2);
});

it('scopes the slot uniqueness per household rather than globally', function (): void {
    $partner = User::create(['name' => 'Partner', 'email' => 'partner@example.test', 'password' => 'test-password']);
    insertMemberRow($this->household, $partner, 2);

    $otherOwner = User::create(['name' => 'Other', 'email' => 'other@example.test', 'password' => 'test-password']);
    $otherHousehold = app(CreateHousehold::class)->handle($otherOwner, 'Keluarga Lain');
    $otherPartner = User::create(['name' => 'Other partner', 'email' => 'other-partner@example.test', 'password' => 'test-password']);

    insertMemberRow($otherHousehold, $otherPartner, 2);

    expect($this->household->members()->count())->toBe(2)
        ->and($otherHousehold->members()->count())->toBe(2);
});
