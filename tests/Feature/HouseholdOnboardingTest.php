<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Actions\CreateHousehold;
use App\Core\Enums\MemberRole;
use App\Core\Models\HouseholdMember;
use App\Livewire\Onboarding\HouseholdWizard;
use App\Mail\HouseholdInvitationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

final class HouseholdOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $attributes = []): User
    {
        static $sequence = 0;
        $sequence++;

        $isAdmin = (bool) ($attributes['is_system_admin'] ?? false);
        unset($attributes['is_system_admin']);

        $user = User::create(array_merge([
            'name' => 'User '.$sequence,
            'email' => 'user'.$sequence.'@example.test',
            'password' => 'Password123!',
        ], $attributes));

        if ($isAdmin) {
            $user->is_system_admin = true;
            $user->save();
        }

        return $user;
    }

    public function test_it_redirects_un_onboarded_owner_from_home_to_onboarding(): void
    {
        $owner = $this->createUser();
        $household = app(CreateHousehold::class)->handle($owner, 'Keluarga Baru');
        $this->assertNull($household->onboarded_at);

        $response = $this->actingAs($owner)->get('/');

        $response->assertRedirect(route('onboarding'));
    }

    public function test_it_allows_onboarded_owner_to_access_home(): void
    {
        $owner = $this->createUser();
        $household = app(CreateHousehold::class)->handle($owner, 'Keluarga Selesai');
        $household->onboarded_at = now()->toImmutable();
        $household->save();

        $response = $this->actingAs($owner)->get('/');

        $response->assertOk();
    }

    public function test_it_does_not_redirect_guest_admin_or_partner_to_onboarding_from_home(): void
    {
        // Guest
        $this->get('/')->assertOk();

        // Admin redirects to admin dashboard, not onboarding
        $admin = $this->createUser(['is_system_admin' => true]);
        $this->actingAs($admin)->get('/')->assertRedirect(route('admin.dashboard'));

        // Partner
        $owner = $this->createUser();
        $household = app(CreateHousehold::class)->handle($owner, 'Keluarga Kita');

        $partner = $this->createUser();
        $member = new HouseholdMember([
            'role' => MemberRole::Partner,
            'color' => MemberRole::Partner->color(),
            'slot' => 2,
            'joined_at' => now(),
        ]);
        $member->user()->associate($partner);
        $household->members()->save($member);

        $this->actingAs($partner)->get('/')->assertOk();
    }

    public function test_it_guards_onboarding_route_against_unauthorized_users(): void
    {
        // Guest -> login
        $this->get(route('onboarding'))->assertRedirect(route('login'));

        // Admin -> home
        $admin = $this->createUser(['is_system_admin' => true]);
        $this->actingAs($admin)->get(route('onboarding'))->assertRedirect('/');

        // Partner -> home
        $owner = $this->createUser();
        $household = app(CreateHousehold::class)->handle($owner, 'Keluarga Kita');

        $partner = $this->createUser();
        $member = new HouseholdMember([
            'role' => MemberRole::Partner,
            'color' => MemberRole::Partner->color(),
            'slot' => 2,
            'joined_at' => now(),
        ]);
        $member->user()->associate($partner);
        $household->members()->save($member);

        $this->actingAs($partner);
        Livewire::test(HouseholdWizard::class)->assertRedirect('/');

        // Onboarded owner -> home
        $household->onboarded_at = now()->toImmutable();
        $household->save();

        $this->actingAs($owner);
        Livewire::test(HouseholdWizard::class)->assertRedirect('/');
    }

    public function test_it_renders_step_1_with_prefilled_household_name(): void
    {
        $owner = $this->createUser();
        $household = app(CreateHousehold::class)->handle($owner, 'Keluarga Mawar');

        $this->actingAs($owner);

        Livewire::test(HouseholdWizard::class)
            ->assertSet('step', 1)
            ->assertSet('householdName', 'Keluarga Mawar')
            ->assertSeeHtml('id="onboarding-household-name"');
    }

    public function test_it_validates_and_saves_step_1(): void
    {
        $owner = $this->createUser();
        $household = app(CreateHousehold::class)->handle($owner, 'Keluarga Awal');

        $this->actingAs($owner);

        // Validation failure
        Livewire::test(HouseholdWizard::class)
            ->set('householdName', '')
            ->call('saveStep1')
            ->assertHasErrors(['householdName'])
            ->assertSet('step', 1);

        // Success
        Livewire::test(HouseholdWizard::class)
            ->set('householdName', 'Keluarga Cemara')
            ->call('saveStep1')
            ->assertHasNoErrors()
            ->assertSet('step', 2);

        $this->assertSame('Keluarga Cemara', $household->fresh()->name);
    }

    public function test_it_validates_and_saves_step_2(): void
    {
        $owner = $this->createUser();
        $household = app(CreateHousehold::class)->handle($owner, 'Keluarga Sukses');

        $this->actingAs($owner);

        // Invalid timezone and start day
        Livewire::test(HouseholdWizard::class)
            ->set('step', 2)
            ->set('timezone', 'Mars/Phobos')
            ->set('startDay', 35)
            ->call('saveStep2')
            ->assertHasErrors(['timezone', 'startDay'])
            ->assertSet('step', 2);

        // Valid update
        Livewire::test(HouseholdWizard::class)
            ->set('step', 2)
            ->set('timezone', 'Asia/Makassar')
            ->set('startDay', 25)
            ->call('saveStep2')
            ->assertHasNoErrors()
            ->assertSet('step', 3);

        $household->refresh();
        $this->assertSame('Asia/Makassar', $household->timezone);
        $this->assertSame(25, $household->finance_period_start_day);

        $setting = $household->financePeriodSettings()->withoutGlobalScopes()->first();
        $this->assertNotNull($setting);
        $this->assertSame(25, $setting->start_day);
    }

    public function test_it_supports_navigation_between_steps(): void
    {
        $owner = $this->createUser();
        app(CreateHousehold::class)->handle($owner, 'Keluarga Harmonis');

        $this->actingAs($owner);

        Livewire::test(HouseholdWizard::class)
            ->assertSet('step', 1)
            ->call('saveStep1')
            ->assertSet('step', 2)
            ->call('previousStep')
            ->assertSet('step', 1)
            ->call('saveStep1')
            ->assertSet('step', 2)
            ->call('saveStep2')
            ->assertSet('step', 3)
            ->call('previousStep')
            ->assertSet('step', 2);
    }

    public function test_it_completes_onboarding_when_skipping_step_3(): void
    {
        Mail::fake();

        $owner = $this->createUser();
        $household = app(CreateHousehold::class)->handle($owner, 'Keluarga Bahagia');

        $this->actingAs($owner);

        Livewire::test(HouseholdWizard::class)
            ->set('step', 3)
            ->call('skip')
            ->assertRedirect('/');

        $this->assertTrue($household->fresh()->isOnboarded());
        Mail::assertNothingSent();
    }

    public function test_it_invites_partner_and_completes_onboarding_on_step_3(): void
    {
        Mail::fake();

        $owner = $this->createUser(['email' => 'owner@keluarga.test']);
        $household = app(CreateHousehold::class)->handle($owner, 'Keluarga Berdua');

        $this->actingAs($owner);

        Livewire::test(HouseholdWizard::class)
            ->set('step', 3)
            ->set('partnerEmail', 'partner@keluarga.test')
            ->call('finish')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertTrue($household->fresh()->isOnboarded());

        $this->assertDatabaseHas('household_invitations', [
            'household_id' => $household->id,
            'email' => 'partner@keluarga.test',
        ]);

        Mail::assertQueued(HouseholdInvitationMail::class, function (HouseholdInvitationMail $mail): bool {
            return $mail->hasTo('partner@keluarga.test');
        });
    }

    public function test_it_shows_validation_error_when_partner_invitation_fails(): void
    {
        Mail::fake();

        $owner = $this->createUser(['email' => 'owner@keluarga.test']);
        $household = app(CreateHousehold::class)->handle($owner, 'Keluarga Uji');

        $this->actingAs($owner);

        // Invalid email format
        Livewire::test(HouseholdWizard::class)
            ->set('step', 3)
            ->set('partnerEmail', 'not-an-email')
            ->call('finish')
            ->assertHasErrors(['partnerEmail']);

        $this->assertFalse($household->fresh()->isOnboarded());
    }
}
