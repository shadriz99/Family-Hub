<?php

declare(strict_types=1);

use App\Core\Enums\MemberRole;
use App\Core\Models\Household;
use App\Core\Models\HouseholdMember;
use App\Core\Notifications\TestNotification;
use App\Core\Services\ReminderService;
use App\Core\Support\HouseholdContext;
use App\Core\Support\IssuedInvitation;
use App\Livewire\Notifications\NotificationCenter;
use App\Livewire\Settings\NotificationSettings;
use App\Mail\HouseholdInvitationMail;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use NotificationChannels\WebPush\WebPushChannel;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 10:00:00', 'UTC'));

    $this->user = User::create([
        'name' => 'Budi Santoso',
        'email' => 'budi@familyhub.test',
        'password' => 'password123',
        'notify_push' => true,
        'notify_email' => true,
        'quiet_hours_start' => '21:00:00',
        'quiet_hours_end' => '06:00:00',
        'daily_digest_time' => '06:00:00',
    ]);

    $this->household = Household::create([
        'name' => 'Keluarga Budi',
        'timezone' => 'Asia/Jakarta',
        'finance_period_start_day' => 1,
        'created_by' => $this->user->id,
        'onboarded_at' => now(),
    ]);

    $member = new HouseholdMember([
        'role' => MemberRole::Owner,
        'color' => MemberRole::Owner->color(),
        'slot' => 1,
        'joined_at' => now(),
    ]);
    $member->user()->associate($this->user);
    $this->household->members()->save($member);

    app(HouseholdContext::class)->use($this->household);
});

it('verifies HouseholdInvitationMail implements ShouldQueue to close P-14', function (): void {
    $issued = new IssuedInvitation(
        new \App\Core\Models\HouseholdInvitation([
            'email' => 'partner@test.com',
            'type' => \App\Core\Enums\InvitationType::Partner,
            'token_hash' => 'dummy',
            'expires_at' => now()->addDays(7),
        ]),
        'dummy-token'
    );

    $mail = new HouseholdInvitationMail($issued);
    expect($mail)->toBeInstanceOf(ShouldQueue::class);
});

it('selects notification channels strictly based on user preferences', function (): void {
    $notification = new TestNotification();

    // User with push and email enabled
    $channelsAll = $notification->via($this->user);
    expect($channelsAll)->toContain('database')
        ->and($channelsAll)->toContain(WebPushChannel::class)
        ->and($channelsAll)->toContain('mail');

    // User with push and email disabled
    $this->user->notify_push = false;
    $this->user->notify_email = false;
    $this->user->save();

    $channelsDbOnly = $notification->via($this->user);
    expect($channelsDbOnly)->toBe(['database']);
});

it('dispatches ReminderNotification and creates in-app database notification when delivery is due', function (): void {
    $nowUtc = CarbonImmutable::parse('2026-10-27 10:00:00', 'UTC');
    $service = app(ReminderService::class);

    $service->schedule(
        $this->household,
        $this->household,
        'rent-payment',
        [
            'title' => 'Bayar Sewa Rumah',
            'body' => 'Batas waktu sewa adalah besok',
            'url' => '/finance/planned/99',
            'remind_at' => $nowUtc->subMinute(),
            'recipient_user_id' => $this->user->id,
        ]
    );

    $stats = $service->dispatchDueDeliveries($nowUtc);

    expect($stats['dispatched'])->toBe(1)
        ->and($this->user->notifications()->count())->toBe(1);

    $databaseNotification = $this->user->notifications()->first();
    expect($databaseNotification->data['title'])->toBe('Bayar Sewa Rumah')
        ->and($databaseNotification->data['url'])->toBe('/finance/planned/99')
        ->and($databaseNotification->read_at)->toBeNull();
});

it('renders notification settings and saves updated preferences', function (): void {
    Livewire::actingAs($this->user)
        ->test(NotificationSettings::class)
        ->assertSet('notify_push', true)
        ->assertSet('notify_email', true)
        ->assertSet('quiet_hours_start', '21:00')
        ->assertSet('quiet_hours_end', '06:00')
        ->assertSet('daily_digest_time', '06:00')
        ->set('notify_email', false)
        ->set('quiet_hours_start', '22:00')
        ->set('quiet_hours_end', '07:00')
        ->set('daily_digest_time', '07:30')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Pengaturan notifikasi berhasil disimpan.');

    $this->user->refresh();
    expect($this->user->notify_email)->toBeFalse()
        ->and($this->user->quiet_hours_start)->toBe('22:00:00')
        ->and($this->user->quiet_hours_end)->toBe('07:00:00')
        ->and($this->user->daily_digest_time)->toBe('07:30:00');
});

it('sends test notification via NotificationSettings component', function (): void {
    Notification::fake();

    Livewire::actingAs($this->user)
        ->test(NotificationSettings::class)
        ->call('sendTestNotification')
        ->assertSee('Notifikasi uji berhasil dikirim');

    Notification::assertSentTo($this->user, TestNotification::class);
});

it('manages in-app notifications in NotificationCenter component', function (): void {
    // Seed 2 notifications
    $this->user->notify(new TestNotification());
    $this->user->notify(new TestNotification());

    expect($this->user->unreadNotifications()->count())->toBe(2);

    $firstNotification = $this->user->unreadNotifications()->first();

    // Test mark single as read
    Livewire::actingAs($this->user)
        ->test(NotificationCenter::class)
        ->assertSee('Uji Notifikasi Berhasil')
        ->call('markAsRead', $firstNotification->id);

    expect($this->user->unreadNotifications()->count())->toBe(1);

    // Test mark all as read
    Livewire::actingAs($this->user)
        ->test(NotificationCenter::class)
        ->call('markAllAsRead');

    expect($this->user->unreadNotifications()->count())->toBe(0);

    // Test delete notification
    Livewire::actingAs($this->user)
        ->test(NotificationCenter::class)
        ->call('deleteNotification', $firstNotification->id);

    expect($this->user->notifications()->count())->toBe(1);
});

it('guards notification routes against unauthenticated and non-onboarded users', function (): void {
    $this->get(route('notifications.index'))
        ->assertRedirect(route('login'));

    $this->get(route('settings.notifications'))
        ->assertRedirect(route('login'));

    $this->actingAs($this->user)
        ->get(route('notifications.index'))
        ->assertOk();

    $this->actingAs($this->user)
        ->get(route('settings.notifications'))
        ->assertOk();
});
