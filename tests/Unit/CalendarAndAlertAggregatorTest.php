<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Alerts\Alert;
use App\Core\Alerts\AlertAggregator;
use App\Core\Alerts\AlertSource;
use App\Core\Calendar\CalendarAggregator;
use App\Core\Calendar\CalendarItem;
use App\Core\Calendar\CalendarSource;
use App\Core\Models\Household;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Tests\TestCase;

class CalendarAndAlertAggregatorTest extends TestCase
{
    private Household $household;

    protected function setUp(): void
    {
        parent::setUp();

        $this->household = new Household();
        $this->household->id = 1;
        $this->household->name = 'Keluarga Bahagia';
        $this->household->timezone = 'Asia/Jakarta';
    }

    public function test_calendar_aggregator_combines_and_sorts_items_from_multiple_sources(): void
    {
        $source1 = new class () implements CalendarSource {
            public function key(): string
            {
                return 'finance.planned';
            }

            public function label(): string
            {
                return 'Tagihan & Cicilan';
            }

            public function items(Household $h, CarbonImmutable $from, CarbonImmutable $to): iterable
            {
                return [
                    new CalendarItem(
                        uid: 'item-2',
                        sourceKey: 'finance.planned',
                        title: 'Tagihan Listrik',
                        start: CarbonImmutable::parse('2026-11-10 09:00:00', $h->timezone),
                        end: null,
                        allDay: true,
                        color: '#EF4444',
                        url: '/finance/planned/2'
                    ),
                ];
            }
        };

        $source2 = new class () implements CalendarSource {
            public function key(): string
            {
                return 'organizer.events';
            }

            public function label(): string
            {
                return 'Acara Keluarga';
            }

            public function items(Household $h, CarbonImmutable $from, CarbonImmutable $to): iterable
            {
                return [
                    new CalendarItem(
                        uid: 'item-1',
                        sourceKey: 'organizer.events',
                        title: 'Arisan Keluarga',
                        start: CarbonImmutable::parse('2026-11-05 14:00:00', $h->timezone),
                        end: CarbonImmutable::parse('2026-11-05 17:00:00', $h->timezone),
                        allDay: false,
                        color: '#3B82F6',
                        url: '/organizer/events/1'
                    ),
                    new CalendarItem(
                        uid: 'item-3',
                        sourceKey: 'organizer.events',
                        title: 'Beli Kado',
                        start: CarbonImmutable::parse('2026-11-10 09:00:00', $h->timezone),
                        end: null,
                        allDay: true,
                        color: '#10B981',
                        url: '/organizer/events/3'
                    ),
                ];
            }
        };

        $aggregator = new CalendarAggregator(sources: [$source1, $source2]);

        $from = CarbonImmutable::parse('2026-11-01 00:00:00', 'Asia/Jakarta');
        $to = CarbonImmutable::parse('2026-11-30 23:59:59', 'Asia/Jakarta');

        $items = $aggregator->between($this->household, $from, $to);

        $this->assertCount(3, $items);
        // Sorted by start asc, tie break title asc:
        // 1. Arisan Keluarga (Nov 5)
        // 2. Beli Kado (Nov 10, 'B' < 'T')
        // 3. Tagihan Listrik (Nov 10)
        $this->assertSame('item-1', $items[0]->uid);
        $this->assertSame('item-3', $items[1]->uid);
        $this->assertSame('item-2', $items[2]->uid);
    }

    public function test_calendar_aggregator_filters_by_source_keys(): void
    {
        $source1 = new class () implements CalendarSource {
            public function key(): string
            {
                return 'finance.planned';
            }

            public function label(): string
            {
                return 'Tagihan';
            }

            public function items(Household $h, CarbonImmutable $from, CarbonImmutable $to): iterable
            {
                return [
                    new CalendarItem(
                        uid: 'fin-1',
                        sourceKey: 'finance.planned',
                        title: 'Tagihan Air',
                        start: CarbonImmutable::parse('2026-11-01 08:00:00', $h->timezone),
                        end: null,
                        allDay: true,
                        color: '#EF4444',
                        url: '/finance/1'
                    ),
                ];
            }
        };

        $source2 = new class () implements CalendarSource {
            public function key(): string
            {
                return 'organizer.events';
            }

            public function label(): string
            {
                return 'Acara';
            }

            public function items(Household $h, CarbonImmutable $from, CarbonImmutable $to): iterable
            {
                return [
                    new CalendarItem(
                        uid: 'org-1',
                        sourceKey: 'organizer.events',
                        title: 'Liburan',
                        start: CarbonImmutable::parse('2026-11-02 08:00:00', $h->timezone),
                        end: null,
                        allDay: true,
                        color: '#3B82F6',
                        url: '/org/1'
                    ),
                ];
            }
        };

        $aggregator = new CalendarAggregator(sources: [$source1, $source2]);

        $from = CarbonImmutable::parse('2026-11-01 00:00:00', 'Asia/Jakarta');
        $to = CarbonImmutable::parse('2026-11-30 23:59:59', 'Asia/Jakarta');

        $filtered = $aggregator->between($this->household, $from, $to, ['finance.planned']);

        $this->assertCount(1, $filtered);
        $this->assertSame('fin-1', $filtered[0]->uid);
    }

    public function test_calendar_aggregator_enforces_max_range_limit_of_400_days(): void
    {
        $aggregator = new CalendarAggregator(sources: []);

        $from = CarbonImmutable::parse('2026-01-01 00:00:00', 'Asia/Jakarta');
        $to = $from->addDays(401);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Date range exceeds maximum allowed range of 400 days.');

        $aggregator->between($this->household, $from, $to);
    }

    public function test_calendar_aggregator_rejects_reversed_date_range(): void
    {
        $aggregator = new CalendarAggregator(sources: []);

        $from = CarbonImmutable::parse('2026-11-10 00:00:00', 'Asia/Jakarta');
        $to = CarbonImmutable::parse('2026-11-01 00:00:00', 'Asia/Jakarta');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Start date ($from) cannot be after end date ($to).');

        $aggregator->between($this->household, $from, $to);
    }

    public function test_calendar_aggregator_resolves_sources_from_container_tags(): void
    {
        $source = new class () implements CalendarSource {
            public function key(): string
            {
                return 'test.source';
            }

            public function label(): string
            {
                return 'Test Source';
            }

            public function items(Household $h, CarbonImmutable $from, CarbonImmutable $to): iterable
            {
                return [
                    new CalendarItem(
                        uid: 'test-1',
                        sourceKey: 'test.source',
                        title: 'Acara Uji Coba',
                        start: $from,
                        end: null,
                        allDay: true,
                        color: '#6366F1',
                        url: '/test/1'
                    ),
                ];
            }
        };

        $this->app->instance('test.calendar.source', $source);
        $this->app->tag('test.calendar.source', 'calendar.sources');

        $aggregator = app(CalendarAggregator::class);

        $from = CarbonImmutable::parse('2026-11-01 00:00:00', 'Asia/Jakarta');
        $to = CarbonImmutable::parse('2026-11-05 00:00:00', 'Asia/Jakarta');

        $items = $aggregator->between($this->household, $from, $to);

        $this->assertCount(1, $items);
        $this->assertSame('test-1', $items[0]->uid);
    }

    public function test_alert_aggregator_combines_and_sorts_by_severity_level(): void
    {
        $source1 = new class () implements AlertSource {
            public function alerts(Household $h): iterable
            {
                return [
                    new Alert(
                        level: 'info',
                        title: 'Sinkronisasi berhasil',
                        url: '/info',
                        sourceKey: 'sync'
                    ),
                    new Alert(
                        level: 'danger',
                        title: 'Anggaran Makanan Habis',
                        url: '/finance/budget/1',
                        sourceKey: 'finance.budget'
                    ),
                ];
            }
        };

        $source2 = new class () implements AlertSource {
            public function alerts(Household $h): iterable
            {
                return [
                    new Alert(
                        level: 'warning',
                        title: 'Cicilan Jatuh Tempo 2 Hari Lagi',
                        url: '/finance/planned/3',
                        sourceKey: 'finance.planned'
                    ),
                    new Alert(
                        level: 'danger',
                        title: 'Akun Belum Terhubung',
                        url: '/settings',
                        sourceKey: 'settings'
                    ),
                ];
            }
        };

        $aggregator = new AlertAggregator(sources: [$source1, $source2]);

        $alerts = $aggregator->for($this->household);

        // Expect priority:
        // danger (Akun Belum Terhubung, Anggaran Makanan Habis)
        // warning (Cicilan Jatuh Tempo 2 Hari Lagi)
        // info (Sinkronisasi berhasil)
        $this->assertCount(4, $alerts);
        $this->assertSame('danger', $alerts[0]->level);
        $this->assertSame('Akun Belum Terhubung', $alerts[0]->title);
        $this->assertSame('danger', $alerts[1]->level);
        $this->assertSame('Anggaran Makanan Habis', $alerts[1]->title);
        $this->assertSame('warning', $alerts[2]->level);
        $this->assertSame('Cicilan Jatuh Tempo 2 Hari Lagi', $alerts[2]->title);
        $this->assertSame('info', $alerts[3]->level);
        $this->assertSame('Sinkronisasi berhasil', $alerts[3]->title);
    }

    public function test_alert_aggregator_caps_results_at_limit(): void
    {
        $source = new class () implements AlertSource {
            public function alerts(Household $h): iterable
            {
                $items = [];
                for ($i = 1; $i <= 10; $i++) {
                    $items[] = new Alert(
                        level: 'warning',
                        title: "Peringatan ke-{$i}",
                        url: "/alert/{$i}",
                        sourceKey: 'test'
                    );
                }

                return $items;
            }
        };

        $aggregator = new AlertAggregator(sources: [$source]);

        $defaultCapped = $aggregator->for($this->household);
        $this->assertCount(5, $defaultCapped);

        $customCapped = $aggregator->for($this->household, limit: 3);
        $this->assertCount(3, $customCapped);

        $allAlerts = $aggregator->for($this->household, limit: null);
        $this->assertCount(10, $allAlerts);
    }

    public function test_alert_aggregator_resolves_sources_from_container_tags(): void
    {
        $source = new class () implements AlertSource {
            public function alerts(Household $h): iterable
            {
                return [
                    new Alert(
                        level: 'warning',
                        title: 'Uji Peringatan Kontainer',
                        url: '/warning',
                        sourceKey: 'container.test'
                    ),
                ];
            }
        };

        $this->app->instance('test.alert.source', $source);
        $this->app->tag('test.alert.source', 'alert.sources');

        $aggregator = app(AlertAggregator::class);

        $alerts = $aggregator->for($this->household);

        $this->assertCount(1, $alerts);
        $this->assertSame('Uji Peringatan Kontainer', $alerts[0]->title);
    }
}
