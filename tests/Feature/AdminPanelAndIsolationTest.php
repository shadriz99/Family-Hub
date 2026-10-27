<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Actions\CreateHousehold;
use App\Core\Models\Household;
use App\Core\Support\HouseholdContext;
use App\Livewire\Admin\AdminDashboard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

final class AdminPanelAndIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $regularUser;

    private Household $household;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-27 10:00:00', 'UTC'));

        $this->admin = User::create([
            'name' => 'Admin System',
            'email' => 'admin@familyhub.test',
            'password' => Hash::make('adminsecret123'),
        ]);
        $this->admin->is_system_admin = true;
        $this->admin->save();

        $this->regularUser = User::create([
            'name' => 'Regular User',
            'email' => 'regular@familyhub.test',
            'password' => Hash::make('usersecret123'),
        ]);

        $this->household = app(CreateHousehold::class)->handle($this->regularUser, 'Keluarga Budi', 1, 'Asia/Jakarta');
        $this->household->forceFill(['onboarded_at' => now()])->save();
    }

    public function test_guest_is_redirected_to_login_when_accessing_admin(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    public function test_non_admin_user_is_forbidden_from_admin_panel(): void
    {
        $this->actingAs($this->regularUser);

        $response = $this->get('/admin');
        $response->assertForbidden();
    }

    public function test_system_admin_can_access_admin_panel_and_see_households(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin');
        $response->assertOk();
        $response->assertSee('Manajemen Sistem & Household');
        $response->assertSee('Keluarga Budi');
        $response->assertSee('1 / 2');
    }

    public function test_admin_can_invite_new_household_and_revoke_invitation(): void
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(AdminDashboard::class)
            ->set('invite_email', 'newowner@familyhub.test')
            ->call('inviteNewHousehold')
            ->assertHasNoErrors()
            ->assertSee(__('Undangan household baru berhasil dikirim.'));

        $this->assertDatabaseHas('household_invitations', [
            'email' => 'newowner@familyhub.test',
            'household_id' => null,
        ]);

        $invite = \App\Core\Models\HouseholdInvitation::whereNull('household_id')->firstOrFail();

        $component->call('revokeInvitation', $invite->id)
            ->assertHasNoErrors()
            ->assertSee(__('Undangan household berhasil dibatalkan.'));

        $this->assertNotNull($invite->fresh()->revoked_at);
    }

    public function test_admin_can_disable_and_enable_user(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminDashboard::class)
            ->call('toggleUserStatus', $this->regularUser->id)
            ->assertHasNoErrors()
            ->assertSee(__('Pengguna berhasil dinonaktifkan.'));

        $this->assertTrue($this->regularUser->fresh()->isDisabled());

        // Admin logs out before testing regular user login attempt
        auth()->logout();

        // Attempting to login with disabled account fails
        $this->post('/login', [
            'email' => 'regular@familyhub.test',
            'password' => 'usersecret123',
        ])->assertSessionHasErrors('email');

        // Admin logs back in to re-enable user
        $this->actingAs($this->admin);

        Livewire::test(AdminDashboard::class)
            ->call('toggleUserStatus', $this->regularUser->id)
            ->assertHasNoErrors()
            ->assertSee(__('Pengguna berhasil diaktifkan kembali.'));

        $this->assertFalse($this->regularUser->fresh()->isDisabled());
    }

    public function test_admin_cannot_disable_themselves_or_other_admins(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminDashboard::class)
            ->call('toggleUserStatus', $this->admin->id)
            ->assertSee(__('Anda tidak dapat menonaktifkan akun sendiri.'));

        $this->assertFalse($this->admin->fresh()->isDisabled());
    }

    /**
     * Acceptance Criteria CORE-15:
     * System admin cannot access individual household domain routes.
     */
    public function test_admin_cannot_access_household_domain_routes(): void
    {
        $this->actingAs($this->admin);

        // Accessing household settings is strictly forbidden for system admin
        $this->get('/settings/household')->assertForbidden();

        // Accessing household activity log is strictly forbidden for system admin
        $this->get('/settings/activity')->assertForbidden();

        // Accessing household notifications is strictly forbidden for system admin
        $this->get('/notifications')->assertForbidden();

        // Household context remains null for system admin
        $this->assertNull(app(HouseholdContext::class)->currentOrNull());
    }
}
