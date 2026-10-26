<?php

declare(strict_types=1);

use App\Core\Enums\MemberRole;
use App\Core\Models\Household;
use App\Core\Models\HouseholdMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->household = Household::create(['name' => 'Synthetic household']);
    $this->owner = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'test-password']);
    $this->partner = User::create(['name' => 'Partner', 'email' => 'partner@example.test', 'password' => 'test-password']);

    foreach ([[$this->owner, MemberRole::Owner, 1], [$this->partner, MemberRole::Partner, 2]] as [$user, $role, $slot]) {
        $member = new HouseholdMember(['role' => $role, 'slot' => $slot, 'color' => $role->color(), 'joined_at' => now()]);
        $member->user()->associate($user);
        $this->household->members()->save($member);
    }
});

it('enforces the owner and partner matrix through Laravel Gate', function (string $ability, bool $owner, bool $partner): void {
    expect(Gate::forUser($this->owner)->allows($ability, $this->household))->toBe($owner)
        ->and(Gate::forUser($this->partner)->allows($ability, $this->household))->toBe($partner);
})->with([
    ['view', true, true], ['update', true, true],
    ['invitePartner', true, false], ['cancelInvitation', true, false],
    ['removePartner', true, false], ['transferOwnership', true, false],
    ['delete', true, false], ['leave', false, true],
]);

it('denies outsiders, guests and administrators without membership', function (string $ability): void {
    $outsider = User::create(['name' => 'Outsider', 'email' => 'outsider@example.test', 'password' => 'test-password']);
    $outsider->forceFill(['is_system_admin' => true])->save();
    $other = Household::create(['name' => 'Other household']);
    $member = new HouseholdMember(['role' => MemberRole::Owner, 'slot' => 1, 'color' => '#059669', 'joined_at' => now()]);
    $member->user()->associate($outsider);
    $other->members()->save($member);

    expect(Gate::forUser($outsider)->allows($ability, $this->household))->toBeFalse()
        ->and(Gate::forUser(null)->allows($ability, $this->household))->toBeFalse()
        ->and(Gate::forUser($this->owner)->allows($ability, $other))->toBeFalse();
})->with(['view', 'update', 'invitePartner', 'cancelInvitation', 'removePartner', 'transferOwnership', 'delete', 'leave']);

it('does not reuse stale role or membership relations after revocation', function (): void {
    $this->owner->load('householdMembership');
    $membership = $this->owner->householdMembership;
    $membership->role = MemberRole::Partner;
    $membership->save();

    expect(Gate::forUser($this->owner)->allows('delete', $this->household))->toBeFalse();
    $membership->delete();
    expect(Gate::forUser($this->owner)->allows('view', $this->household))->toBeFalse();
});

it('denies deleted households even when the caller holds a stale model', function (): void {
    $stale = Household::findOrFail($this->household->id);
    $this->household->delete();
    expect(Gate::forUser($this->owner)->allows('view', $stale))->toBeFalse()
        ->and(Gate::forUser($this->owner)->allows('delete', $stale))->toBeFalse();
});

it('does not elevate administrator permissions within their own household', function (): void {
    $this->partner->forceFill(['is_system_admin' => true])->save();
    expect(Gate::forUser($this->partner)->allows('view', $this->household))->toBeTrue()
        ->and(Gate::forUser($this->partner)->allows('delete', $this->household))->toBeFalse();
});
