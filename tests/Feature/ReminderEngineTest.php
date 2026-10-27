<?php

declare(strict_types=1);

use App\Core\Actions\AcceptInvitation;
use App\Core\Actions\InviteHouseholdMember;
use App\Core\Enums\MemberRole;
use App\Core\Enums\ReminderDeliveryStatus;
use App\Core\Enums\ReminderStatus;
use App\Core\Models\Household;
use App\Core\Models\HouseholdMember;
use App\Core\Models\Reminder;
use App\Core\Models\ReminderDelivery;
use App\Core\Services\ReminderService;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 10:00:00', 'UTC'));

    $this->owner = User::create([
        'name' => 'Owner User',
        'email' => 'owner@familyhub.test',
        'password' => 'password123',
        'quiet_hours_start' => '21:00:00',
        'quiet_hours_end' => '06:00:00',
    ]);

    $this->partner = User::create([
        'name' => 'Partner User',
        'email' => 'partner@familyhub.test',
        'password' => 'password123',
        'quiet_hours_start' => '23:00:00',
        'quiet_hours_end' => '05:00:00',
    ]);

    $this->household = Household::create([
        'name' => 'Keluarga Bahagia',
        'timezone' => 'Asia/Jakarta',
        'finance_period_start_day' => 1,
        'created_by' => $this->owner->id,
    ]);

    $member1 = new HouseholdMember([
        'role' => MemberRole::Owner,
        'color' => MemberRole::Owner->color(),
        'slot' => 1,
        'joined_at' => now(),
    ]);
    $member1->user()->associate($this->owner);
    $this->household->members()->save($member1);

    $member2 = new HouseholdMember([
        'role' => MemberRole::Partner,
        'color' => MemberRole::Partner->color(),
        'slot' => 2,
        'joined_at' => now(),
    ]);
    $member2->user()->associate($this->partner);
    $this->household->members()->save($member2);

    app(HouseholdContext::class)->use($this->household);

    $this->reminderService = app(ReminderService::class);
});

it('schedules a logical reminder and creates per-recipient deliveries for all household members', function (): void {
    // 14:00 Asia/Jakarta = 07:00 UTC (daytime outside quiet hours)
    $remindAt = CarbonImmutable::parse('2026-10-28 07:00:00', 'UTC');

    $reminder = $this->reminderService->schedule(
        $this->household,
        $this->household, // Using household as dummy remindable
        'bill-due-h3',
        [
            'title' => 'Bayar Tagihan Listrik',
            'body' => 'Tagihan listrik jatuh tempo 3 hari lagi',
            'url' => '/finance/bills/123',
            'remind_at' => $remindAt,
        ],
        $this->owner
    );

    expect($reminder)->toBeInstanceOf(Reminder::class)
        ->and($reminder->status)->toBe(ReminderStatus::Scheduled)
        ->and($reminder->title)->toBe('Bayar Tagihan Listrik')
        ->and($reminder->recipient_user_id)->toBeNull();

    $deliveries = ReminderDelivery::query()->where('reminder_id', $reminder->id)->get();
    expect($deliveries)->toHaveCount(2);

    $ownerDelivery = $deliveries->firstWhere('recipient_user_id', $this->owner->id);
    $partnerDelivery = $deliveries->firstWhere('recipient_user_id', $this->partner->id);

    expect($ownerDelivery)->not->toBeNull()
        ->and($ownerDelivery->status)->toBe(ReminderDeliveryStatus::Pending)
        ->and($ownerDelivery->remind_at->toIso8601String())->toBe('2026-10-28T07:00:00+00:00')
        ->and($partnerDelivery)->not->toBeNull()
        ->and($partnerDelivery->status)->toBe(ReminderDeliveryStatus::Pending);
});

it('enforces idempotency and updates reminder without duplicate records', function (): void {
    $remindAt = CarbonImmutable::parse('2026-10-28 07:00:00', 'UTC');

    $first = $this->reminderService->schedule(
        $this->household,
        $this->household,
        'task-due',
        [
            'title' => 'Beli Susu',
            'url' => '/organizer/tasks/1',
            'remind_at' => $remindAt,
        ]
    );

    $second = $this->reminderService->schedule(
        $this->household,
        $this->household,
        'task-due',
        [
            'title' => 'Beli Susu Kotak 1L',
            'url' => '/organizer/tasks/1',
            'remind_at' => $remindAt,
        ]
    );

    expect($second->id)->toBe($first->id)
        ->and(Reminder::query()->count())->toBe(1)
        ->and(ReminderDelivery::query()->count())->toBe(2)
        ->and($second->title)->toBe('Beli Susu Kotak 1L');
});

it('isolates quiet hours so only the affected recipient delivery is postponed', function (): void {
    // 22:00 Asia/Jakarta = 15:00 UTC
    // Owner quiet hours: 21:00 - 06:00 -> inside quiet hours, shifted to 06:00 next day (23:00 UTC)
    // Partner quiet hours: 23:00 - 05:00 -> 22:00 is outside quiet hours, remains 15:00 UTC
    $remindAt = CarbonImmutable::parse('2026-10-28 15:00:00', 'UTC');

    $reminder = $this->reminderService->schedule(
        $this->household,
        $this->household,
        'evening-alert',
        [
            'title' => 'Periksa Pintu Rumah',
            'url' => '/alerts',
            'remind_at' => $remindAt,
        ]
    );

    $ownerDelivery = ReminderDelivery::query()
        ->where('reminder_id', $reminder->id)
        ->where('recipient_user_id', $this->owner->id)
        ->first();

    $partnerDelivery = ReminderDelivery::query()
        ->where('reminder_id', $reminder->id)
        ->where('recipient_user_id', $this->partner->id)
        ->first();

    // Owner delivery shifted to next morning 06:00 Asia/Jakarta = 2026-10-28 23:00:00 UTC
    expect($ownerDelivery->remind_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'))
        ->toBe('2026-10-29 06:00:00');

    // Partner delivery unchanged at 22:00 Asia/Jakarta = 2026-10-28 15:00:00 UTC
    expect($partnerDelivery->remind_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'))
        ->toBe('2026-10-28 22:00:00');
});

it('allows targeting a single specific user instead of the entire household', function (): void {
    $remindAt = CarbonImmutable::parse('2026-10-28 07:00:00', 'UTC');

    $reminder = $this->reminderService->schedule(
        $this->household,
        $this->household,
        'personal-memo',
        [
            'title' => 'Catatan Khusus Partner',
            'url' => '/memo',
            'remind_at' => $remindAt,
            'recipient_user_id' => $this->partner->id,
        ]
    );

    $deliveries = ReminderDelivery::query()->where('reminder_id', $reminder->id)->get();
    expect($deliveries)->toHaveCount(1)
        ->and($deliveries->first()->recipient_user_id)->toBe($this->partner->id);
});

it('cancels logical reminder and cascades cancellation to pending deliveries', function (): void {
    $remindAt = CarbonImmutable::parse('2026-10-28 07:00:00', 'UTC');

    $reminder = $this->reminderService->schedule(
        $this->household,
        $this->household,
        'cancellable',
        [
            'title' => 'Pengingat Sementara',
            'url' => '/tmp',
            'remind_at' => $remindAt,
        ]
    );

    $this->reminderService->cancel($reminder);

    expect($reminder->fresh()->status)->toBe(ReminderStatus::Cancelled);

    $deliveries = ReminderDelivery::query()->where('reminder_id', $reminder->id)->get();
    foreach ($deliveries as $delivery) {
        expect($delivery->status)->toBe(ReminderDeliveryStatus::Cancelled);
    }
});

it('synchronizes future household reminders when a partner joins via invitation', function (): void {
    Mail::fake();

    // Create a 1-member household
    $newOwner = User::create([
        'name' => 'Single Owner',
        'email' => 'single@familyhub.test',
        'password' => 'password123',
    ]);

    $soloHousehold = Household::create([
        'name' => 'Solo Family',
        'timezone' => 'Asia/Jakarta',
        'finance_period_start_day' => 1,
        'created_by' => $newOwner->id,
    ]);

    $member = new HouseholdMember([
        'role' => MemberRole::Owner,
        'color' => MemberRole::Owner->color(),
        'slot' => 1,
        'joined_at' => now(),
    ]);
    $member->user()->associate($newOwner);
    $soloHousehold->members()->save($member);

    app(HouseholdContext::class)->use($soloHousehold);

    // Schedule future reminder for the household
    $futureTime = CarbonImmutable::now('UTC')->addDays(2);
    $this->reminderService->schedule(
        $soloHousehold,
        $soloHousehold,
        'future-event',
        [
            'title' => 'Acara Mendatang',
            'url' => '/events',
            'remind_at' => $futureTime,
        ]
    );

    expect(ReminderDelivery::query()->where('household_id', $soloHousehold->id)->count())->toBe(1);

    // Invite partner
    $inviteAction = app(InviteHouseholdMember::class);
    $issued = $inviteAction->handle($newOwner, $soloHousehold, 'newpartner@familyhub.test');

    $newPartner = User::create([
        'name' => 'New Partner',
        'email' => 'newpartner@familyhub.test',
        'password' => 'password123',
    ]);

    // Accept invitation
    $acceptAction = app(AcceptInvitation::class);
    $acceptAction->handle($newPartner, $issued->token);

    // Assert deliveries synced for new partner
    $deliveries = ReminderDelivery::query()->where('household_id', $soloHousehold->id)->get();
    expect($deliveries)->toHaveCount(2)
        ->and($deliveries->where('recipient_user_id', $newPartner->id))->toHaveCount(1);
});

it('dispatches pending due deliveries within household tenant context', function (): void {
    $nowUtc = CarbonImmutable::parse('2026-10-27 10:00:00', 'UTC');

    $reminder = $this->reminderService->schedule(
        $this->household,
        $this->household,
        'due-alert',
        [
            'title' => 'Waktunya Minum Obat',
            'url' => '/health',
            'remind_at' => $nowUtc->subMinute(),
        ]
    );

    $dispatchedInContext = false;
    $context = app(HouseholdContext::class);

    $stats = $this->reminderService->dispatchDueDeliveries($nowUtc, function (ReminderDelivery $delivery) use ($context, &$dispatchedInContext): bool {
        // Assert ambient household context is set properly
        if ($context->currentOrNull()?->id === $delivery->household_id) {
            $dispatchedInContext = true;
        }

        return true;
    });

    expect($stats['dispatched'])->toBe(2)
        ->and($dispatchedInContext)->toBeTrue();

    $deliveries = ReminderDelivery::query()->where('reminder_id', $reminder->id)->get();
    foreach ($deliveries as $delivery) {
        expect($delivery->status)->toBe(ReminderDeliveryStatus::Sent)
            ->and($delivery->sent_at)->not->toBeNull();
    }
});

it('retries failures with staged backoff and terminates as failed after 3 attempts per Q-M9', function (): void {
    $nowUtc = CarbonImmutable::parse('2026-10-27 10:00:00', 'UTC');

    $reminder = $this->reminderService->schedule(
        $this->household,
        $this->household,
        'retry-test',
        [
            'title' => 'Uji Coba Gagal',
            'url' => '/retry',
            'remind_at' => $nowUtc->subMinute(),
            'recipient_user_id' => $this->owner->id,
        ]
    );

    $delivery = ReminderDelivery::query()->where('reminder_id', $reminder->id)->sole();

    // Attempt 1 fails: backoff 300s (5 min)
    $stats1 = $this->reminderService->dispatchDueDeliveries($nowUtc, fn () => false);
    $delivery->refresh();
    expect($stats1['retried'])->toBe(1)
        ->and($delivery->attempt_count)->toBe(1)
        ->and($delivery->status)->toBe(ReminderDeliveryStatus::Pending)
        ->and($delivery->next_attempt_at->toIso8601String())->toBe('2026-10-27T10:05:00+00:00');

    // Try again before next_attempt_at: should be skipped
    $statsSkip = $this->reminderService->dispatchDueDeliveries($nowUtc->addMinutes(2), fn () => false);
    expect($statsSkip['retried'])->toBe(0);

    // Attempt 2 fails at +5m: backoff 900s (15 min) -> +20m
    $stats2 = $this->reminderService->dispatchDueDeliveries($nowUtc->addMinutes(5), fn () => false);
    $delivery->refresh();
    expect($stats2['retried'])->toBe(1)
        ->and($delivery->attempt_count)->toBe(2)
        ->and($delivery->status)->toBe(ReminderDeliveryStatus::Pending)
        ->and($delivery->next_attempt_at->toIso8601String())->toBe('2026-10-27T10:20:00+00:00');

    // Attempt 3 fails at +20m: max attempts reached, terminal FAILED
    $stats3 = $this->reminderService->dispatchDueDeliveries($nowUtc->addMinutes(20), fn () => false);
    $delivery->refresh();
    expect($stats3['failed'])->toBe(1)
        ->and($delivery->attempt_count)->toBe(3)
        ->and($delivery->status)->toBe(ReminderDeliveryStatus::Failed)
        ->and($delivery->next_attempt_at)->toBeNull()
        ->and($delivery->last_error_code)->toBe('SENDER_RETURNED_FALSE');
});

it('runs reminders:dispatch command via artisan successfully', function (): void {
    $nowUtc = CarbonImmutable::parse('2026-10-27 10:00:00', 'UTC');

    $this->reminderService->schedule(
        $this->household,
        $this->household,
        'command-test',
        [
            'title' => 'Pengujian Artisan Command',
            'url' => '/test',
            'remind_at' => $nowUtc->subMinute(),
        ]
    );

    Artisan::call('reminders:dispatch');

    expect(Artisan::output())->toContain('Reminder deliveries dispatched: 2');
});
