<?php

declare(strict_types=1);

use App\Core\Actions\AcceptInvitation;
use App\Core\Actions\CreateHousehold;
use App\Core\Actions\InviteHouseholdMember;
use App\Core\Actions\RevokeHouseholdInvitation;
use App\Core\Enums\MemberRole;
use App\Core\Models\Activity;
use App\Core\Models\Household;
use App\Core\Models\HouseholdInvitation;
use App\Core\Models\HouseholdMember;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->owner = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'test-password']);
    $this->guestUser = User::create(['name' => 'Guest', 'email' => 'guest@example.test', 'password' => 'test-password']);
    $this->household = app(CreateHousehold::class)->handle($this->owner, 'Keluarga Uji');
    $this->travelTo(Carbon::parse('2026-10-26 10:00:00', 'UTC'));
});

it('issues a hashed invitation that expires in seven days', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household);

    $stored = HouseholdInvitation::query()->sole();

    expect($issued->token)->toHaveLength(64)
        ->and($stored->is($issued->invitation))->toBeTrue()
        ->and($stored->token_hash)->toBe(hash('sha256', $issued->token))
        ->and($stored->token_hash)->not->toBe($issued->token)
        ->and($stored->invitedBy->is($this->owner))->toBeTrue()
        ->and($stored->expires_at->toIso8601String())->toBe('2026-11-02T10:00:00+00:00')
        ->and($stored->accepted_at)->toBeNull()
        ->and($issued->invitation->household->is($this->household))->toBeTrue();

    $log = Activity::query()->where('description', 'core.household.partner_invited')->sole();
    expect($log->subject->is($this->household))->toBeTrue()
        ->and($log->causer->is($this->owner))->toBeTrue()
        ->and($log->getProperty('invitation_id'))->toBe($stored->getKey());
});

it('admits the invited user as the partner exactly once', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household);

    $member = app(AcceptInvitation::class)->handle($this->guestUser, $issued->token);

    expect($member->role)->toBe(MemberRole::Partner)
        ->and($member->slot)->toBe(2)
        ->and($member->color)->toBe(MemberRole::Partner->color())
        ->and($member->user->is($this->guestUser))->toBeTrue()
        ->and($this->guestUser->householdMembership()->sole()->is($member))->toBeTrue();

    $invitation = HouseholdInvitation::query()->sole();
    expect($invitation->accepted_at)->not->toBeNull()
        ->and($invitation->acceptedBy->is($this->guestUser))->toBeTrue()
        ->and(Activity::query()->where('description', 'core.household.partner_joined')->exists())->toBeTrue();
});

it('rejects a second use of the same token', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household);
    app(AcceptInvitation::class)->handle($this->guestUser, $issued->token);

    $other = User::create(['name' => 'Other', 'email' => 'other@example.test', 'password' => 'test-password']);

    expect(fn () => app(AcceptInvitation::class)->handle($other, $issued->token))
        ->toThrow(ValidationException::class);

    expect($this->household->members()->count())->toBe(2)
        ->and($other->householdMembership()->exists())->toBeFalse()
        ->and(HouseholdInvitation::query()->sole()->acceptedBy->is($this->guestUser))->toBeTrue();
});

it('still accepts the token right up to the expiry instant', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household);
    $this->travelTo(Carbon::parse('2026-11-02 09:59:59', 'UTC'));

    $member = app(AcceptInvitation::class)->handle($this->guestUser, $issued->token);

    expect($member->slot)->toBe(2);
});

it('rejects an expired invitation without consuming it', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household);
    $this->travelTo(Carbon::parse('2026-11-02 10:00:01', 'UTC'));

    expect(fn () => app(AcceptInvitation::class)->handle($this->guestUser, $issued->token))
        ->toThrow(ValidationException::class);

    expect($this->guestUser->householdMembership()->exists())->toBeFalse()
        ->and(HouseholdInvitation::query()->sole()->accepted_at)->toBeNull();
});

it('rejects unknown and tampered tokens', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household);
    $tampered = ($issued->token[0] === 'a' ? 'b' : 'a').substr($issued->token, 1);

    foreach ([str_repeat('a', 64), $tampered, ''] as $candidate) {
        expect(fn () => app(AcceptInvitation::class)->handle($this->guestUser, $candidate))
            ->toThrow(ValidationException::class);
    }

    expect($this->guestUser->householdMembership()->exists())->toBeFalse()
        ->and(HouseholdInvitation::query()->sole()->accepted_at)->toBeNull();
});

it('rejects an invitation once the household is full', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household);
    $spare = app(InviteHouseholdMember::class)->handle($this->owner, $this->household)->token;
    app(AcceptInvitation::class)->handle($this->guestUser, $issued->token);

    $other = User::create(['name' => 'Other', 'email' => 'other@example.test', 'password' => 'test-password']);

    expect(fn () => app(AcceptInvitation::class)->handle($other, $spare))
        ->toThrow(ValidationException::class);

    expect($this->household->members()->count())->toBe(2);
});

it('rejects a user who already belongs to another household', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household);
    $otherHousehold = Household::create(['name' => 'Other household']);
    $outsider = User::create(['name' => 'Outsider', 'email' => 'outsider@example.test', 'password' => 'test-password']);
    $member = new HouseholdMember([
        'role' => MemberRole::Owner,
        'color' => MemberRole::Owner->color(),
        'slot' => 1,
        'joined_at' => now(),
    ]);
    $member->user()->associate($outsider);
    $otherHousehold->members()->save($member);

    expect(fn () => app(AcceptInvitation::class)->handle($outsider, $issued->token))
        ->toThrow(ValidationException::class);

    expect($this->household->members()->count())->toBe(1)
        ->and($otherHousehold->members()->count())->toBe(1)
        ->and(HouseholdInvitation::query()->sole()->accepted_at)->toBeNull();
});

it('ignores an invitation whose household was deleted', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household);
    $this->household->delete();

    expect(fn () => app(AcceptInvitation::class)->handle($this->guestUser, $issued->token))
        ->toThrow(ValidationException::class);

    expect($this->guestUser->householdMembership()->exists())->toBeFalse();
});

it('refuses invitations from partners and outsiders', function (string $actor): void {
    if ($actor === 'partner') {
        $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household);
        app(AcceptInvitation::class)->handle($this->guestUser, $issued->token);
        $user = $this->guestUser;
    } else {
        $user = User::create(['name' => 'Other', 'email' => 'other@example.test', 'password' => 'test-password']);
    }

    expect(fn () => app(InviteHouseholdMember::class)->handle($user, $this->household))
        ->toThrow(AuthorizationException::class);

    expect(HouseholdInvitation::query()->count())->toBe($actor === 'partner' ? 1 : 0);
})->with(['partner', 'outsider']);

it('revokes a pending invitation and refuses to revoke it afterwards', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household);

    app(RevokeHouseholdInvitation::class)->handle($this->owner, $this->household, $issued->invitation);

    expect(HouseholdInvitation::query()->count())->toBe(0)
        ->and(Activity::query()->where('description', 'core.household.invitation_revoked')->exists())->toBeTrue();

    expect(fn () => app(AcceptInvitation::class)->handle($this->guestUser, $issued->token))
        ->toThrow(ValidationException::class);
});

it('rejects duplicate token hashes at the database level', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household);

    $duplicate = new HouseholdInvitation([
        'token_hash' => $issued->invitation->token_hash,
        'expires_at' => now()->addDay(),
    ]);
    $duplicate->household()->associate($this->household);
    $duplicate->invitedBy()->associate($this->owner);

    expect(fn () => $duplicate->save())->toThrow(QueryException::class);

    expect(HouseholdInvitation::query()->count())->toBe(1);
});

it('refuses to revoke a foreign invitation', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household);
    $foreign = Household::create(['name' => 'Other household']);

    expect(fn () => app(RevokeHouseholdInvitation::class)->handle($this->owner, $foreign, $issued->invitation))
        ->toThrow(AuthorizationException::class);

    expect(HouseholdInvitation::query()->count())->toBe(1);
});

it('refuses to revoke an already accepted invitation', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household);
    app(AcceptInvitation::class)->handle($this->guestUser, $issued->token);

    expect(fn () => app(RevokeHouseholdInvitation::class)->handle($this->owner, $this->household, $issued->invitation))
        ->toThrow(ValidationException::class);

    expect(HouseholdInvitation::query()->count())->toBe(1)
        ->and($this->household->members()->count())->toBe(2);
});
