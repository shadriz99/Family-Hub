<?php

declare(strict_types=1);

use App\Core\Alerts\Alert;
use App\Core\Alerts\AlertSource;
use App\Core\Calendar\CalendarItem;
use App\Core\Calendar\CalendarSource;
use App\Core\Dashboard\DashboardManager;
use App\Core\Dashboard\DashboardWidget;
use App\Core\Enums\MemberRole;
use App\Core\Models\Household;
use App\Core\Models\HouseholdMember;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 08:30:00', 'UTC'));

    $this->user = User::create([
        'name' => 'Citra Dewi',
        'email' => 'citra@familyhub.test',
        'password' => 'password123',
    ]);

    $this->household = Household::create([
        'name' => 'Keluarga Bahagia',
        'timezone' => 'Asia/Jakarta',
        'finance_period_start_day' => 15,
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

it('renders time-of-day greetings correctly across hours', function (): void {
    $manager = app(DashboardManager::class);

    // 07:00 Asia/Jakarta -> Pagi
    $pagi = CarbonImmutable::parse('2026-10-27 00:00:00', 'UTC'); // 07:00 WIB
    expect($manager->greeting('Citra', $pagi, 'Asia/Jakarta'))->toBe('Selamat pagi, Citra');

    // 13:00 Asia/Jakarta -> Siang
    $siang = CarbonImmutable::parse('2026-10-27 06:00:00', 'UTC'); // 13:00 WIB
    expect($manager->greeting('Citra', $siang, 'Asia/Jakarta'))->toBe('Selamat siang, Citra');

    // 16:30 Asia/Jakarta -> Sore
    $sore = CarbonImmutable::parse('2026-10-27 09:30:00', 'UTC'); // 16:30 WIB
    expect($manager->greeting('Citra', $sore, 'Asia/Jakarta'))->toBe('Selamat sore, Citra');

    // 21:00 Asia/Jakarta -> Malam
    $malam = CarbonImmutable::parse('2026-10-27 14:00:00', 'UTC'); // 21:00 WIB
    expect($manager->greeting('Citra', $malam, 'Asia/Jakarta'))->toBe('Selamat malam, Citra');
});

it('renders dashboard with alerts, calendar items, and household info', function (): void {
    // Register dummy alert source
    app()->bind('dummy.alert', fn () => new class () implements AlertSource {
        public function key(): string
        {
            return 'dummy';
        }
        public function label(): string
        {
            return 'Dummy';
        }
        public function alerts(Household $h): iterable
        {
            return [
                new Alert('danger', 'Tagihan PLN Menunggak', '/finance/bills/1', 'finance'),
                new Alert('info', 'Jadwal Servis AC', '/asset/1', 'asset'),
            ];
        }
    });
    app()->tag('dummy.alert', 'alert.sources');

    // Register dummy calendar source
    app()->bind('dummy.cal', fn () => new class () implements CalendarSource {
        public function key(): string
        {
            return 'dummy';
        }
        public function label(): string
        {
            return 'Dummy';
        }
        public function items(Household $h, CarbonImmutable $f, CarbonImmutable $t): array
        {
            $today = CarbonImmutable::parse('2026-10-27 10:00:00', 'UTC');
            $nextWeek = CarbonImmutable::parse('2026-10-30 14:00:00', 'UTC');

            return [
                new CalendarItem('cal-1', 'dummy', 'Belanja Mingguan', $today, null, false, '#059669', '/tasks/1'),
                new CalendarItem('cal-2', 'dummy', 'Periksa Pajak Kendaraan', $nextWeek, null, false, '#386578', '/asset/2'),
            ];
        }
    });
    app()->tag('dummy.cal', 'calendar.sources');

    $response = $this->actingAs($this->user)->get('/');

    $response->assertOk()
        ->assertSee('Tagihan PLN Menunggak')
        ->assertSee('Jadwal Servis AC')
        ->assertSee('Belanja Mingguan')
        ->assertSee('Periksa Pajak Kendaraan')
        ->assertSee('Asia/Jakarta')
        ->assertSee('Tanggal 15')
        ->assertSee('1 / 2');
});

it('filters dynamic module widgets based on module feature flags', function (): void {
    $activeWidget = new class () implements DashboardWidget {
        public function key(): string
        {
            return 'core_summary';
        }
        public function module(): string
        {
            return 'core';
        }
        public function order(): int
        {
            return 1;
        }
        public function isEnabled(Household $h): bool
        {
            return true;
        }
        public function render(Household $h): string
        {
            return '<div id="core-widget">Ringkasan Inti</div>';
        }
    };

    $inactiveWidget = new class () implements DashboardWidget {
        public function key(): string
        {
            return 'finance_chart';
        }
        public function module(): string
        {
            return 'finance';
        }
        public function order(): int
        {
            return 2;
        }
        public function isEnabled(Household $h): bool
        {
            return true;
        }
        public function render(Household $h): string
        {
            return '<div id="fin-widget">Grafik Keuangan</div>';
        }
    };

    app()->instance('widget.active', $activeWidget);
    app()->instance('widget.inactive', $inactiveWidget);
    app()->tag(['widget.active', 'widget.inactive'], 'dashboard.widgets');

    config(['modules.finance' => false]);

    $manager = app(DashboardManager::class);
    $widgets = $manager->widgets($this->household);

    expect($widgets)->toHaveCount(1)
        ->and($widgets[0]->key())->toBe('core_summary');

    $response = $this->actingAs($this->user)->get('/');
    $response->assertOk()
        ->assertSee('Ringkasan Inti')
        ->assertDontSee('Grafik Keuangan');
});
