<?php

declare(strict_types=1);

use App\Core\Actions\AcceptNewHouseholdInvitation;
use App\Core\Actions\InviteNewHousehold;
use App\Core\Enums\InvitationType;
use App\Core\Enums\MemberRole;
use App\Core\Models\Activity;
use App\Core\Models\HouseholdInvitation;
use App\Mail\HouseholdInvitationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('id');
    $this->travelTo(Carbon::parse('2026-10-27 10:00:00', 'UTC'));

    $this->admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin@example.test',
        'password' => 'admin-password',
    ]);
    $this->admin->is_system_admin = true;
    $this->admin->save();

    $this->regularUser = User::create([
        'name' => 'Regular User',
        'email' => 'regular@example.test',
        'password' => 'user-password',
    ]);
});

it('allows a system admin to issue a new-household invitation', function (): void {
    Mail::fake();

    $issued = app(InviteNewHousehold::class)->handle($this->admin, ' prospective-owner@example.test ');

    $stored = HouseholdInvitation::query()->sole();

    expect($issued->token)->toHaveLength(64)
        ->and($stored->email)->toBe('prospective-owner@example.test')
        ->and($stored->type)->toBe(InvitationType::NewHousehold)
        ->and($stored->household_id)->toBeNull()
        ->and($stored->invitedBy->is($this->admin))->toBeTrue()
        ->and($stored->expires_at->toIso8601String())->toBe('2026-11-03T10:00:00+00:00')
        ->and($stored->accepted_at)->toBeNull()
        ->and($stored->revoked_at)->toBeNull();

    Mail::assertQueued(HouseholdInvitationMail::class, function (HouseholdInvitationMail $mail) use ($issued): bool {
        return $mail->hasTo('prospective-owner@example.test')
            && str_contains($mail->render(), route('invitation.show', $issued->token));
    });

    $log = Activity::query()->where('description', 'core.household.new_household_invited')->sole();
    expect($log->causer->is($this->admin))->toBeTrue();
});

it('refuses new-household invitations from non-admin users', function (): void {
    expect(fn () => app(InviteNewHousehold::class)->handle($this->regularUser, 'target@example.test'))
        ->toThrow(ValidationException::class);

    expect(HouseholdInvitation::query()->count())->toBe(0);
});

it('revokes previous pending new-household invitations for the same email', function (): void {
    Mail::fake();

    $first = app(InviteNewHousehold::class)->handle($this->admin, 'target@example.test');
    $second = app(InviteNewHousehold::class)->handle($this->admin, 'target@example.test');

    expect($first->invitation->fresh()->revoked_at)->not->toBeNull()
        ->and($second->invitation->fresh()->revoked_at)->toBeNull();
});

it('caps admin invitations at ten per hour', function (): void {
    Mail::fake();

    for ($i = 1; $i <= 10; $i++) {
        app(InviteNewHousehold::class)->handle($this->admin, "user{$i}@example.test");
    }

    expect(fn () => app(InviteNewHousehold::class)->handle($this->admin, 'user11@example.test'))
        ->toThrow(ValidationException::class);
});

it('admits a user creating a new household via invitation', function (): void {
    Mail::fake();

    $issued = app(InviteNewHousehold::class)->handle($this->admin, 'owner@example.test');

    $user = User::create([
        'name' => 'Owner Person',
        'email' => 'owner@example.test',
        'password' => 'password123',
    ]);

    $household = app(AcceptNewHouseholdInvitation::class)->handle($user, $issued->token, 'Keluarga Baru');

    expect($household->name)->toBe('Keluarga Baru')
        ->and($household->members)->toHaveCount(1)
        ->and($household->members->first()->user->is($user))->toBeTrue()
        ->and($household->members->first()->role)->toBe(MemberRole::Owner)
        ->and($issued->invitation->fresh()->accepted_at)->not->toBeNull();
});

it('rejects an admin accepting a new-household invitation', function (): void {
    Mail::fake();

    $issued = app(InviteNewHousehold::class)->handle($this->admin, $this->admin->email);

    expect(fn () => app(AcceptNewHouseholdInvitation::class)->handle($this->admin, $issued->token, 'Keluarga Admin'))
        ->toThrow(ValidationException::class);
});

it('rejects new-household invitation acceptance if email does not match', function (): void {
    Mail::fake();

    $issued = app(InviteNewHousehold::class)->handle($this->admin, 'intended@example.test');

    $otherUser = User::create([
        'name' => 'Other',
        'email' => 'other@example.test',
        'password' => 'password123',
    ]);

    expect(fn () => app(AcceptNewHouseholdInvitation::class)->handle($otherUser, $issued->token, 'Keluarga'))
        ->toThrow(ValidationException::class);
});

it('renders the new-household invitation acceptance page and registers owner', function (): void {
    Mail::fake();

    $issued = app(InviteNewHousehold::class)->handle($this->admin, 'prospective@example.test');

    $this->get(route('invitation.show', $issued->token))
        ->assertOk()
        ->assertSeeLivewire('auth.accept-invitation-form')
        ->assertSee('prospective@example.test')
        ->assertSee(__('auth.invite_for_new_household', ['email' => 'prospective@example.test']))
        ->assertSee(__('auth.household_name'));

    Livewire::test('auth.accept-invitation-form', ['token' => $issued->token])
        ->set('name', 'Calon Owner')
        ->set('householdName', 'Keluarga Mandiri')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('onboarding'));

    $user = User::query()->where('email', 'prospective@example.test')->sole();
    expect($user->name)->toBe('Calon Owner');

    $invitation = $issued->invitation->fresh();
    expect($invitation->accepted_at)->not->toBeNull();
});
