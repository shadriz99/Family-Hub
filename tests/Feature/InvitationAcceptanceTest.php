<?php

declare(strict_types=1);

use App\Core\Actions\AcceptInvitation;
use App\Core\Actions\CreateHousehold;
use App\Core\Actions\InviteHouseholdMember;
use App\Core\Actions\RevokeHouseholdInvitation;
use App\Core\Enums\MemberRole;
use App\Core\Models\HouseholdInvitation;
use App\Livewire\Auth\AcceptInvitationForm;
use App\Mail\HouseholdInvitationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('id');
    $this->travelTo(Carbon::parse('2026-10-26 10:00:00', 'UTC'));

    $this->owner = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'test-password']);
    $this->household = app(CreateHousehold::class)->handle($this->owner, 'Keluarga Uji');
});

it('emails the invitation link to the invited address', function (): void {
    Mail::fake();

    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'partner@example.test');

    Mail::assertQueued(HouseholdInvitationMail::class, function (HouseholdInvitationMail $mail) use ($issued): bool {
        return $mail->hasTo('partner@example.test')
            && str_contains($mail->render(), route('invitation.show', $issued->token));
    });
});

it('renders the acceptance page with the household and invited email', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'partner@example.test');

    $this->get(route('invitation.show', $issued->token))
        ->assertOk()
        ->assertSeeLivewire('auth.accept-invitation-form')
        ->assertSee('Keluarga Uji')
        ->assertSee('partner@example.test')
        ->assertSee('Buat akun dan bergabung');
});

it('shows a friendly message for an unknown token', function (): void {
    $this->get(route('invitation.show', str_repeat('a', 64)))
        ->assertOk()
        ->assertSee(__('auth.invite_invalid'));
});

it('creates the account, admits the partner, and signs them in', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'partner@example.test');

    Livewire::test(AcceptInvitationForm::class, ['token' => $issued->token])
        ->set('name', 'Partner')
        ->set('password', 'partner-secret')
        ->set('password_confirmation', 'partner-secret')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect('/');

    $user = User::query()->where('email', 'partner@example.test')->sole();

    expect($user->name)->toBe('Partner')
        ->and(Hash::check('partner-secret', (string) $user->password))->toBeTrue()
        ->and($user->householdMembership?->role)->toBe(MemberRole::Partner)
        ->and($user->householdMembership?->household_id)->toBe($this->household->getKey())
        ->and(HouseholdInvitation::query()->sole()->accepted_at)->not->toBeNull();

    $this->assertAuthenticatedAs($user);
});

it('rejects invalid registration input without creating an account', function (): void {
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'partner@example.test');

    Livewire::test(AcceptInvitationForm::class, ['token' => $issued->token])
        ->set('name', '')
        ->set('password', 'short')
        ->set('password_confirmation', 'different')
        ->call('submit')
        ->assertHasErrors(['name', 'password']);

    expect(User::query()->where('email', 'partner@example.test')->exists())->toBeFalse()
        ->and($this->household->members()->count())->toBe(1);
});

it('reports expired, used, and revoked invitations without showing the form', function (): void {
    // One active invitation per household (P-13), so each state gets its own household.
    $expired = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'expired@example.test');

    $usedOwner = User::create(['name' => 'Used owner', 'email' => 'used-owner@example.test', 'password' => 'test-password']);
    $usedHousehold = app(CreateHousehold::class)->handle($usedOwner, 'Keluarga Terpakai');
    $used = app(InviteHouseholdMember::class)->handle($usedOwner, $usedHousehold, 'used@example.test');

    $revokedOwner = User::create(['name' => 'Revoked owner', 'email' => 'revoked-owner@example.test', 'password' => 'test-password']);
    $revokedHousehold = app(CreateHousehold::class)->handle($revokedOwner, 'Keluarga Dicabut');
    $revoked = app(InviteHouseholdMember::class)->handle($revokedOwner, $revokedHousehold, 'revoked@example.test');

    app(RevokeHouseholdInvitation::class)->handle($revokedOwner, $revokedHousehold, $revoked->invitation);

    $guest = User::create(['name' => 'Guest', 'email' => 'used@example.test', 'password' => 'test-password']);
    app(AcceptInvitation::class)->handle($guest, $used->token);

    $this->travelTo(Carbon::parse('2026-11-02 10:00:01', 'UTC'));

    $this->get(route('invitation.show', $expired->token))
        ->assertSee(__('auth.invite_expired'));

    $this->get(route('invitation.show', $used->token))
        ->assertSee(__('auth.invite_used'));

    $this->get(route('invitation.show', $revoked->token))
        ->assertSee(__('auth.invite_invalid'));
});

it('asks an existing account to sign in before accepting', function (): void {
    $guest = User::create(['name' => 'Guest', 'email' => 'guest@example.test', 'password' => 'test-password']);
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'guest@example.test');

    Livewire::test(AcceptInvitationForm::class, ['token' => $issued->token])
        ->assertSet('existingAccount', true)
        ->assertSee(__('auth.invite_existing', ['email' => 'guest@example.test']))
        ->call('submit')
        ->assertHasErrors('invitation');

    expect($guest->householdMembership()->exists())->toBeFalse();
});

it('admits a signed-in user whose email matches the invitation', function (): void {
    $guest = User::create(['name' => 'Guest', 'email' => 'guest@example.test', 'password' => 'test-password']);
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'guest@example.test');

    $this->actingAs($guest);

    Livewire::test(AcceptInvitationForm::class, ['token' => $issued->token])
        ->assertSet('signedInAsInvitee', true)
        ->assertSee(__('auth.invite_join'))
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($guest->householdMembership()->exists())->toBeTrue();
});

it('offers sign out instead of joining when another account is signed in', function (): void {
    User::create(['name' => 'Guest', 'email' => 'guest@example.test', 'password' => 'test-password']);
    $other = User::create(['name' => 'Other', 'email' => 'other@example.test', 'password' => 'test-password']);
    $issued = app(InviteHouseholdMember::class)->handle($this->owner, $this->household, 'guest@example.test');

    $this->actingAs($other);

    Livewire::test(AcceptInvitationForm::class, ['token' => $issued->token])
        ->assertSet('signedInAsInvitee', false)
        ->assertSee(__('auth.invite_wrong_account', ['email' => 'other@example.test']))
        ->assertSee(__('auth.invite_logout'))
        ->call('submit')
        ->assertHasErrors('invitation');

    expect($other->householdMembership()->exists())->toBeFalse();
});
