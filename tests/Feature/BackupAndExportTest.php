<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Actions\CreateHousehold;
use App\Core\Models\Household;
use App\Core\Services\HouseholdExportService;
use App\Core\Support\HouseholdContext;
use App\Livewire\Settings\HouseholdSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

final class BackupAndExportTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Household $household;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-27 10:00:00', 'UTC'));

        $this->owner = User::create([
            'name' => 'Export User',
            'email' => 'export@example.com',
            'password' => 'secret1234',
        ]);

        $this->household = app(CreateHousehold::class)->handle($this->owner, 'Keluarga Arsip', 1, 'Asia/Jakarta');
        $this->household->forceFill(['onboarded_at' => now()])->save();
        app(HouseholdContext::class)->use($this->household);
    }

    public function test_household_export_service_creates_valid_zip_archive(): void
    {
        /** @var HouseholdExportService $service */
        $service = app(HouseholdExportService::class);

        $zipPath = $service->exportToZip($this->household);

        $this->assertFileExists($zipPath);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath));

        // Check required files inside ZIP
        $this->assertNotFalse($zip->locateName('manifest.json'));
        $this->assertNotFalse($zip->locateName('core/household.json'));
        $this->assertNotFalse($zip->locateName('core/members.json'));
        $this->assertNotFalse($zip->locateName('core/period_settings.json'));

        $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
        $this->assertSame($this->household->id, $manifest['household_id']);
        $this->assertSame('Keluarga Arsip', $manifest['name']);

        $householdData = json_decode((string) $zip->getFromName('core/household.json'), true);
        $this->assertSame('Keluarga Arsip', $householdData['name']);
        $this->assertSame('Asia/Jakarta', $householdData['timezone']);

        $zip->close();

        // Cleanup
        File::delete($zipPath);
    }

    public function test_owner_can_trigger_export_download_via_settings(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(HouseholdSettings::class)
            ->call('exportData')
            ->assertFileDownloaded();

        $this->assertDatabaseHas('activity_log', [
            'description' => 'core.household.exported',
        ]);
    }
}
