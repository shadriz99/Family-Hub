<?php

declare(strict_types=1);

namespace Tests\Feature\Organizer;

use App\Core\Actions\CreateHousehold;
use App\Core\Models\Household;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Organizer\Enums\EventKind;
use App\Modules\Organizer\Livewire\Calendar\Index as CalendarIndex;
use App\Modules\Organizer\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class CalendarAndExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Household $household;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password123'),
        ]);

        $this->household = app(CreateHousehold::class)->handle(
            $this->user,
            'Keluarga Budi',
            1,
            'Asia/Jakarta'
        );
        $this->household->onboarded_at = now();
        $this->household->save();

        app(HouseholdContext::class)->use($this->household);
    }

    public function test_calendar_index_renders_and_creates_quick_event(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CalendarIndex::class)
            ->set('eventTitle', 'Ulang Tahun Ani')
            ->set('eventKind', 'birthday')
            ->set('eventStartDate', '2026-11-20')
            ->call('createQuickEvent');

        $this->assertDatabaseHas('org_events', [
            'household_id' => $this->household->id,
            'title' => 'Ulang Tahun Ani',
            'rrule' => 'FREQ=YEARLY',
        ]);
    }

    public function test_ics_export_returns_valid_icalendar_feed(): void
    {
        $this->actingAs($this->user);

        app(HouseholdContext::class)->runInContext($this->household, function () {
            Event::create([
                'household_id' => $this->household->id,
                'created_by' => $this->user->id,
                'kind' => EventKind::Event,
                'title' => 'Kontrol Rutin Dokter Anak',
                'all_day' => true,
                'start_date' => '2026-11-15',
                'end_date' => '2026-11-16',
                'location' => 'RSIA Bunda',
            ]);
        });

        $response = $this->get('/calendar/export?from=2026-11-01&to=2026-11-30');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
        $this->assertStringContainsString('BEGIN:VCALENDAR', $response->getContent());
        $this->assertStringContainsString('SUMMARY:Kontrol Rutin Dokter Anak', $response->getContent());
        $this->assertStringContainsString('END:VCALENDAR', $response->getContent());
    }
}
