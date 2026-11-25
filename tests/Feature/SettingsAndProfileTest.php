<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Actions\CreateHousehold;
use App\Core\Enums\MemberRole;
use App\Core\Models\Household;
use App\Core\Models\HouseholdMember;
use App\Core\Support\HouseholdContext;
use App\Livewire\Settings\ActivityLogViewer;
use App\Livewire\Settings\HouseholdSettings;
use App\Livewire\Settings\ProfileSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

final class SettingsAndProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Household $household;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'password' => 'password123',
        ]);

        $this->household = app(CreateHousehold::class)->handle($this->owner, 'Keluarga Bahagia', 1, 'Asia/Jakarta');
        $this->household->forceFill(['onboarded_at' => now()])->save();
        app(HouseholdContext::class)->use($this->household);
    }

    public function test_user_can_view_and_update_profile_information(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(ProfileSettings::class)
            ->assertSet('name', 'Owner User')
            ->assertSet('email', 'owner@example.com')
            ->set('name', 'Owner Baru')
            ->set('email', 'ownerbaru@example.com')
            ->call('updateProfile')
            ->assertHasNoErrors()
            ->assertSee(__('Informasi profil berhasil diperbarui.'));

        $this->owner->refresh();
        $this->assertSame('Owner Baru', $this->owner->name);
        $this->assertSame('ownerbaru@example.com', $this->owner->email);
    }

    public function test_user_can_change_password(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(ProfileSettings::class)
            ->set('current_password', 'password123')
            ->set('password', 'newsecretpassword')
            ->set('password_confirmation', 'newsecretpassword')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSee(__('Kata sandi berhasil diperbarui.'));

        $this->owner->refresh();
        $this->assertTrue(Hash::check('newsecretpassword', $this->owner->password));
    }

    public function test_owner_can_update_household_details(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(HouseholdSettings::class)
            ->assertSet('household_name', 'Keluarga Bahagia')
            ->set('household_name', 'Keluarga Sejahtera')
            ->set('timezone', 'Asia/Makassar')
            ->set('finance_period_start_day', 25)
            ->call('updateHousehold')
            ->assertHasNoErrors()
            ->assertSee(__('Pengaturan keluarga berhasil diperbarui.'));

        $this->household->refresh();
        $this->assertSame('Keluarga Sejahtera', $this->household->name);
        $this->assertSame('Asia/Makassar', $this->household->timezone);
        $this->assertSame(25, $this->household->finance_period_start_day);
    }

    public function test_owner_can_invite_partner_and_cancel_invitation(): void
    {
        $this->actingAs($this->owner);

        $component = Livewire::test(HouseholdSettings::class)
            ->set('partner_email', 'partner@example.com')
            ->call('invitePartner')
            ->assertHasNoErrors()
            ->assertSee(__('Undangan berhasil dikirim'));

        $this->assertDatabaseHas('household_invitations', [
            'household_id' => $this->household->id,
            'email' => 'partner@example.com',
        ]);

        $invite = $this->household->invitations()->firstOrFail();

        $component->call('cancelInvitation', $invite->id)
            ->assertHasNoErrors()
            ->assertSee(__('Undangan berhasil dibatalkan.'));

        $this->assertNotNull($invite->fresh()->revoked_at);
    }

    public function test_owner_can_transfer_ownership_and_remove_partner(): void
    {
        $partnerUser = User::create([
            'name' => 'Partner User',
            'email' => 'partner@example.com',
            'password' => 'password123',
        ]);

        $partnerMember = new HouseholdMember([
            'role' => MemberRole::Partner,
            'color' => MemberRole::Partner->color(),
            'slot' => 2,
            'joined_at' => now(),
        ]);
        $partnerMember->user()->associate($partnerUser);
        $this->household->members()->save($partnerMember);

        $this->actingAs($this->owner);

        Livewire::test(HouseholdSettings::class)
            ->call('transferOwnership', $partnerUser->id)
            ->assertHasNoErrors()
            ->assertSee(__('Kepemilikan household berhasil dialihkan.'));

        $this->assertSame(MemberRole::Partner, $this->household->members()->where('user_id', $this->owner->id)->first()->role);
        $this->assertSame(MemberRole::Owner, $this->household->members()->where('user_id', $partnerUser->id)->first()->role);

        // Now acting as partnerUser (who is now owner), remove previous owner (who is now partner)
        $this->actingAs($partnerUser);

        Livewire::test(HouseholdSettings::class)
            ->call('removePartner', $this->owner->id)
            ->assertHasNoErrors()
            ->assertSee(__('Partner berhasil dihapus dari household.'));

        $this->assertNull($this->household->members()->where('user_id', $this->owner->id)->first());
    }

    public function test_partner_can_leave_household(): void
    {
        $partnerUser = User::create([
            'name' => 'Partner User',
            'email' => 'partner@example.com',
            'password' => 'password123',
        ]);

        $partnerMember = new HouseholdMember([
            'role' => MemberRole::Partner,
            'color' => MemberRole::Partner->color(),
            'slot' => 2,
            'joined_at' => now(),
        ]);
        $partnerMember->user()->associate($partnerUser);
        $this->household->members()->save($partnerMember);

        $this->actingAs($partnerUser);

        Livewire::test(HouseholdSettings::class)
            ->call('leaveHousehold')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertNull($this->household->members()->where('user_id', $partnerUser->id)->first());
    }

    public function test_activity_log_viewer_renders_household_events(): void
    {
        $this->actingAs($this->owner);

        activity()
            ->performedOn($this->household)
            ->causedBy($this->owner)
            ->log('core.household.updated');

        Livewire::test(ActivityLogViewer::class)
            ->assertSee(__('Log Aktivitas'))
            ->assertSee(__('Memperbarui data dan pengaturan keluarga'))
            ->assertSee('Owner User');
    }

    public function test_user_can_generate_telegram_code_and_disconnect(): void
    {
        $this->actingAs($this->owner);

        $test = Livewire::test(ProfileSettings::class)
            ->assertSee(__('Integrasi Chatbot Telegram'))
            ->assertSee(__('Belum Terhubung'))
            ->call('generateTelegramCode')
            ->assertHasNoErrors()
            ->assertSee(__('Kode tautan baru telah dibuat'));

        $link = \App\Modules\Finance\Models\TelegramLink::where('user_id', $this->owner->id)->first();
        $this->assertNotNull($link);
        $this->assertNotNull($link->link_code);

        // Simulate connected
        $link->update([
            'telegram_chat_id' => '12345678',
            'telegram_username' => 'owner_tg',
        ]);

        Livewire::test(ProfileSettings::class)
            ->assertSee(__('Terhubung'))
            ->assertSee('@owner_tg')
            ->call('disconnectTelegram')
            ->assertHasNoErrors()
            ->assertSee(__('Sambungan Telegram berhasil diputuskan.'));

        $link->refresh();
        $this->assertNull($link->telegram_chat_id);
    }
}
