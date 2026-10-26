<?php

declare(strict_types=1);

use App\Core\Actions\AcceptInvitation;
use App\Core\Actions\CreateHousehold;
use App\Core\Actions\InviteHouseholdMember;
use App\Core\Actions\RevokeHouseholdInvitation;
use App\Core\Enums\InvitationType;
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

it('issues a hashed partner invitation for the given email that expires in seven days', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, ' Partner@Example.TEST ');

    $stored = HouseholdInvitation::query()->sole();

    expect($issued->token)->toHaveLength(64)
        ->and($stored->is($issued->invitation))->toBeTrue()
        ->and($stored->email)->toBe('partner@example.test')
        ->and($stored->type)->toBe(InvitationType::Partner)
        ->and($stored->token_hash)->toBe(hash('sha256', $issued->token))
        ->and($stored->token_hash)->not->toBe($issued->token)
        ->and($stored->invitedBy->is($this->owner))->toBeTrue()
        ->and($stored->expires_at->toIso8601String())->toBe('2026-11-02T10:00:00+00:00')
        ->and($stored->accepted_at)->toBeNull()
        ->and($stored->revoked_at)->toBeNull()
        ->and($stored->household->is($this->household))->toBeTrue();

    $log = Activity::query()->where('description', 'core.household.partner_invited')->sole();
    expect($log->subject->is($this->household))->toBeTrue()
        ->and($log->causer->is($this->owner))->toBeTrue()
        ->and($log->getProperty('invitation_id'))->toBe($stored->getKey());
});

it('rejects invalid invitation emails without writing anything', function (string $email): void {
    expect(fn () => app(InviteHouseholdMember::class)->handle($this->owner, $this->household, $email))
        ->toThrow(ValidationException::class);

    expect(HouseholdInvitation::query()->count())->toBe(0);
})->with([
    'blank' => ['   '],
    'no at sign' => ['partner.example.test'],
    'no domain' => ['partner@'],
    'too long' => [str_repeat('a', 250).'@example.test'],
]);

it('caps invitation issuance at ten per hour per owner', function (): void {
    foreach (range(1, InviteHouseholdMember::MAX_PER_HOUR) as $index) {
        app(InviteHouseholdMember::class)->handle($this->owner, $this->household, "partner{$index}@example.test");
    }

    expect(fn () => app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'eleventh@example.test'))
        ->toThrow(ValidationException::class, __('household.invite_throttled'));

    expect(HouseholdInvitation::query()->count())->toBe(InviteHouseholdMember::MAX_PER_HOUR);
});

it('does not spend the invite budget on rejected attempts', function (): void {
    foreach (range(1, 12) as $attempt) {
        expect(fn () => app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'not-an-email'))
            ->toThrow(ValidationException::class, __('household.email_invalid'));
    }

    app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'partner@example.test');

    expect(HouseholdInvitation::query()->count())->toBe(1);
});

it('allows invitations again once the hourly window passes', function (): void {
    foreach (range(1, InviteHouseholdMember::MAX_PER_HOUR) as $index) {
        app(InviteHouseholdMember::class)->handle($this->owner, $this->household, "partner{$index}@example.test");
    }

    $this->travel(61)->minutes();

    app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'later@example.test');

    expect(HouseholdInvitation::query()->count())->toBe(InviteHouseholdMember::MAX_PER_HOUR + 1);
});

it('admits the invited user as the partner exactly once', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'guest@example.test');

    $member = app(AcceptInvitation::class)->handle($this->guestUser, $issued->token);

    expect($member->role)->toBe(MemberRole::Partner)
        ->and($member->slot)->toBe(2)
        ->and($member->color)->toBe(MemberRole::Partner->color())
        ->and($member->user->is($this->guestUser))->toBeTrue()
        ->and($this->guestUser->householdMembership()->sole()->is($member))->toBeTrue();

    $invitation = HouseholdInvitation::query()->sole();
    expect($invitation->accepted_at)->not->toBeNull()
        ->and(Activity::query()->where('description', 'core.household.partner_joined')->exists())->toBeTrue();
});

it('rejects a second use of the same token', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'guest@example.test');
    app(AcceptInvitation::class)->handle($this->guestUser, $issued->token);

    $other = User::create(['name' => 'Other', 'email' => 'other@example.test', 'password' => 'test-password']);

    expect(fn () => app(AcceptInvitation::class)->handle($other, $issued->token))
        ->toThrow(ValidationException::class);

    expect($this->household->members()->count())->toBe(2)
        ->and($other->householdMembership()->exists())->toBeFalse()
        ->and(HouseholdInvitation::query()->sole()->accepted_at)->not->toBeNull();
});

it('still accepts the token right up to the expiry instant', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'guest@example.test');
    $this->travelTo(Carbon::parse('2026-11-02 09:59:59', 'UTC'));

    $member = app(AcceptInvitation::class)->handle($this->guestUser, $issued->token);

    expect($member->slot)->toBe(2);
});

it('rejects an expired invitation without consuming it', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'guest@example.test');
    $this->travelTo(Carbon::parse('2026-11-02 10:00:01', 'UTC'));

    expect(fn () => app(AcceptInvitation::class)->handle($this->guestUser, $issued->token))
        ->toThrow(ValidationException::class);

    expect($this->guestUser->householdMembership()->exists())->toBeFalse()
        ->and(HouseholdInvitation::query()->sole()->accepted_at)->toBeNull();
});

it('rejects unknown and tampered tokens', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'guest@example.test');
    $tampered = ($issued->token[0] === 'a' ? 'b' : 'a').substr($issued->token, 1);

    foreach ([str_repeat('a', 64), $tampered, ''] as $candidate) {
        expect(fn () => app(AcceptInvitation::class)->handle($this->guestUser, $candidate))
            ->toThrow(ValidationException::class);
    }

    expect($this->guestUser->householdMembership()->exists())->toBeFalse()
        ->and(HouseholdInvitation::query()->sole()->accepted_at)->toBeNull();
});

it('rejects an invitation once the household is full', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'guest@example.test');
    $spare = User::create(['name' => 'Spare', 'email' => 'spare@example.test', 'password' => 'test-password']);

    // Only one invitation may be active (P-13), so the second slot is filled directly
    // instead of through another invite.
    $member = new HouseholdMember([
        'role' => MemberRole::Partner,
        'color' => MemberRole::Partner->color(),
        'slot' => 2,
        'joined_at' => now(),
    ]);
    $member->user()->associate($spare);
    $this->household->members()->save($member);

    expect(fn () => app(AcceptInvitation::class)->handle($this->guestUser, $issued->token))
        ->toThrow(ValidationException::class, __('household.invite_full'));

    expect($this->household->members()->count())->toBe(2)
        ->and($this->guestUser->householdMembership()->exists())->toBeFalse()
        ->and(HouseholdInvitation::query()->sole()->accepted_at)->toBeNull();
});

it('rejects a token presented by a different email address', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'invited@example.test');
    $stranger = User::create(['name' => 'Stranger', 'email' => 'stranger@example.test', 'password' => 'test-password']);

    expect(fn () => app(AcceptInvitation::class)->handle($stranger, $issued->token))
        ->toThrow(ValidationException::class, __('household.invitation_email_mismatch'));

    expect($stranger->householdMembership()->exists())->toBeFalse()
        ->and($this->household->members()->count())->toBe(1)
        ->and(HouseholdInvitation::query()->sole()->accepted_at)->toBeNull();
});

it('rejects a system admin accepting a partner invitation', function (): void {
    $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-password']);
    $admin->is_system_admin = true;
    $admin->save();

    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'admin@example.test');

    expect(fn () => app(AcceptInvitation::class)->handle($admin, $issued->token))
        ->toThrow(ValidationException::class, __('household.admin_not_member'));

    expect($admin->householdMembership()->exists())->toBeFalse()
        ->and($this->household->members()->count())->toBe(1)
        ->and(HouseholdInvitation::query()->sole()->accepted_at)->toBeNull();
});

it('rejects a user who already belongs to another household', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'outsider@example.test');
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
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'guest@example.test');
    $this->household->delete();

    expect(fn () => app(AcceptInvitation::class)->handle($this->guestUser, $issued->token))
        ->toThrow(ValidationException::class);

    expect($this->guestUser->householdMembership()->exists())->toBeFalse();
});

it('refuses invitations from partners and outsiders', function (string $actor): void {
    if ($actor === 'partner') {
        $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'guest@example.test');
        app(AcceptInvitation::class)->handle($this->guestUser, $issued->token);
        $user = $this->guestUser;
    } else {
        $user = User::create(['name' => 'Other', 'email' => 'other@example.test', 'password' => 'test-password']);
    }

    expect(fn () => app(InviteHouseholdMember::class)->handle($user, $this->household, 'new@example.test'))
        ->toThrow(AuthorizationException::class);

    expect(HouseholdInvitation::query()->count())->toBe($actor === 'partner' ? 1 : 0);
})->with(['partner', 'outsider']);

it('revokes by stamping revoked_at and keeps the row', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'guest@example.test');

    app(RevokeHouseholdInvitation::class)->handle($this->owner, $this->household, $issued->invitation);

    $revoked = HouseholdInvitation::query()->sole();
    expect($revoked->revoked_at)->not->toBeNull()
        ->and($revoked->accepted_at)->toBeNull()
        ->and(Activity::query()->where('description', 'core.household.invitation_revoked')->exists())->toBeTrue();

    expect(fn () => app(AcceptInvitation::class)->handle($this->guestUser, $issued->token))
        ->toThrow(ValidationException::class);

    expect(fn () => app(RevokeHouseholdInvitation::class)->handle($this->owner, $this->household, $revoked))
        ->toThrow(ValidationException::class);

    expect($this->guestUser->householdMembership()->exists())->toBeFalse();
});

it('refuses to revoke a foreign invitation', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'guest@example.test');
    $foreign = Household::create(['name' => 'Other household']);

    expect(fn () => app(RevokeHouseholdInvitation::class)->handle($this->owner, $foreign, $issued->invitation))
        ->toThrow(AuthorizationException::class);

    expect(HouseholdInvitation::query()->sole()->revoked_at)->toBeNull();
});

it('refuses to revoke an already accepted invitation', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'guest@example.test');
    app(AcceptInvitation::class)->handle($this->guestUser, $issued->token);

    expect(fn () => app(RevokeHouseholdInvitation::class)->handle($this->owner, $this->household, $issued->invitation))
        ->toThrow(ValidationException::class);

    expect(HouseholdInvitation::query()->sole()->revoked_at)->toBeNull()
        ->and($this->household->members()->count())->toBe(2);
});

it('retires the previous pending invite when the owner issues a new one', function (): void {
    $first = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'guest@example.test');
    $second = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'guest@example.test');

    $active = HouseholdInvitation::query()->whereNull('accepted_at')->whereNull('revoked_at')->get();

    expect($active)->toHaveCount(1)
        ->and($active->sole()->is($second->invitation))->toBeTrue()
        ->and($first->invitation->fresh()?->revoked_at)->not->toBeNull();

    expect(fn () => app(AcceptInvitation::class)->handle($this->guestUser, $first->token))
        ->toThrow(ValidationException::class, __('household.invitation_invalid'));

    app(AcceptInvitation::class)->handle($this->guestUser, $second->token);

    expect($this->household->members()->count())->toBe(2);
});

it('rejects a new-household invitation through the partner path', function (): void {
    $admin = User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-password']);
    $token = bin2hex(random_bytes(32));
    $invitation = new HouseholdInvitation([
        'email' => 'newowner@example.test',
        'type' => InvitationType::NewHousehold,
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addDay(),
    ]);
    $invitation->invitedBy()->associate($admin);
    $invitation->save();

    expect($invitation->household_id)->toBeNull();

    expect(fn () => app(AcceptInvitation::class)->handle($this->guestUser, $token))
        ->toThrow(ValidationException::class);

    expect($this->guestUser->householdMembership()->exists())->toBeFalse()
        ->and(HouseholdInvitation::query()->sole()->accepted_at)->toBeNull();
});

it('rejects duplicate token hashes at the database level', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'guest@example.test');

    $duplicate = new HouseholdInvitation([
        'email' => 'other@example.test',
        'type' => InvitationType::Partner,
        'token_hash' => $issued->invitation->token_hash,
        'expires_at' => now()->addDay(),
    ]);
    $duplicate->household()->associate($this->household);
    $duplicate->invitedBy()->associate($this->owner);

    expect(fn () => $duplicate->save())->toThrow(QueryException::class);

    expect(HouseholdInvitation::query()->count())->toBe(1);
});
