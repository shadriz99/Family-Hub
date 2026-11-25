<?php

declare(strict_types=1);

use App\Core\Enums\MemberRole;
use App\Core\Models\Household;
use App\Core\Models\HouseholdMember;
use App\Core\Notifications\DailyDigestNotification;
use App\Models\User;
use App\Modules\Organizer\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Notification::fake();
    Storage::fake('local');
    Storage::fake('public');

    $this->user = User::create([
        'name' => 'Budi Santoso',
        'email' => 'budi@familyhub.test',
        'password' => 'password123',
        'notify_email' => true,
        'daily_digest_time' => '07:00:00',
    ]);

    $this->household = Household::create([
        'name' => 'Keluarga Budi',
        'timezone' => 'Asia/Jakarta',
        'finance_period_start_day' => 1,
    ]);

    $member = new HouseholdMember([
        'user_id' => $this->user->id,
        'role' => MemberRole::Owner,
        'color' => MemberRole::Owner->color(),
        'slot' => 1,
        'joined_at' => now(),
    ]);
    $member->household()->associate($this->household);
    $member->save();

    app(\App\Core\Support\HouseholdContext::class)->use($this->household);
});

test('digest:send skips user when outside 15-minute scheduled window without force', function (): void {
    // 06:30 Asia/Jakarta is 23:30 UTC previous day -> outside 07:00 +- 15m window
    $this->travelTo(CarbonImmutable::parse('2026-11-12 06:30:00', 'Asia/Jakarta'));

    Artisan::call('digest:send');

    Notification::assertNothingSent();
    expect($this->user->fresh()->last_daily_digest_sent_on)->toBeNull();
});

test('digest:send suppresses digest when all agenda, tasks, and bills are empty', function (): void {
    // 07:05 Asia/Jakarta is within 07:00 window
    $this->travelTo(CarbonImmutable::parse('2026-11-12 07:05:00', 'Asia/Jakarta'));

    Artisan::call('digest:send');

    Notification::assertNothingSent();
    expect($this->user->fresh()->last_daily_digest_sent_on)->toBeNull();
});

test('digest:send sends notification when tasks or agenda items exist', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-11-12 07:05:00', 'Asia/Jakarta'));

    // Create a due task for today
    Task::create([
        'household_id' => $this->household->id,
        'title' => 'Beli Susu Anak',
        'due_date' => '2026-11-12',
        'status' => 'open',
        'priority' => 'high',
        'created_by' => $this->user->id,
    ]);

    Artisan::call('digest:send');

    Notification::assertSentTo($this->user, DailyDigestNotification::class, function ($notification) {
        $data = $notification->digestData;
        expect($data['total_items'])->toBeGreaterThan(0)
            ->and($data['tasks'])->toHaveCount(1)
            ->and($data['tasks'][0]['title'])->toBe('Beli Susu Anak');
        return true;
    });

    expect($this->user->fresh()->last_daily_digest_sent_on?->format('Y-m-d'))->toBe('2026-11-12');
});

test('digest:send is idempotent and will not re-send on same date unless forced', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-11-12 07:05:00', 'Asia/Jakarta'));

    $this->user->update(['last_daily_digest_sent_on' => '2026-11-12']);

    Task::create([
        'household_id' => $this->household->id,
        'title' => 'Bayar Listrik',
        'due_date' => '2026-11-12',
        'status' => 'open',
        'created_by' => $this->user->id,
    ]);

    Artisan::call('digest:send');

    Notification::assertNothingSent();

    // With --force option, it should send regardless
    Artisan::call('digest:send', ['--force' => true]);

    Notification::assertSentTo($this->user, DailyDigestNotification::class);
});

test('organizer:roll-recurring-tasks creates next task instance when completed', function (): void {
    $seriesId = (string) \Illuminate\Support\Str::ulid();

    $task = Task::create([
        'household_id' => $this->household->id,
        'title' => 'Ganti Filter Air',
        'series_id' => $seriesId,
        'rrule' => 'FREQ=MONTHLY;INTERVAL=1',
        'due_date' => '2026-11-01',
        'status' => 'done',
        'completed_at' => now(),
        'created_by' => $this->user->id,
    ]);

    Artisan::call('organizer:roll-recurring-tasks');

    $tasks = Task::where('series_id', $seriesId)->get();
    expect($tasks)->toHaveCount(2);

    $openTask = $tasks->firstWhere('status', \App\Modules\Organizer\Enums\TaskStatus::Open);
    expect($openTask)->not->toBeNull()
        ->and($openTask->title)->toBe('Ganti Filter Air')
        ->and($openTask->due_date?->format('Y-m-d'))->toBe('2026-12-01');

    // Calling it again should not duplicate (BR-ORG-001: exactly one open instance)
    Artisan::call('organizer:roll-recurring-tasks');
    expect(Task::where('series_id', $seriesId)->count())->toBe(2);
});

test('households:purge-deleted retains recent deleted households and removes expired ones', function (): void {
    $recentHousehold = Household::create([
        'name' => 'Keluarga Baru Hapus',
        'timezone' => 'Asia/Jakarta',
    ]);
    $recentHousehold->delete(); // deleted today

    $expiredHousehold = Household::create([
        'name' => 'Keluarga Lama Hapus',
        'timezone' => 'Asia/Jakarta',
    ]);
    $expiredHousehold->delete();
    // Simulate deleted 35 days ago
    $expiredHousehold->deleted_at = CarbonImmutable::now()->subDays(35);
    $expiredHousehold->save();

    // Create a dummy file in storage for the expired household
    Storage::disk('local')->put("households/{$expiredHousehold->id}/export.zip", 'binary-content');

    Artisan::call('households:purge-deleted');

    // Recent household should still exist in trash
    expect(Household::onlyTrashed()->find($recentHousehold->id))->not->toBeNull();

    // Expired household should be completely erased from DB and storage
    expect(Household::withTrashed()->find($expiredHousehold->id))->toBeNull();
    expect(Storage::disk('local')->exists("households/{$expiredHousehold->id}"))->toBeFalse();
});
