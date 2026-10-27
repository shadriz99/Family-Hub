<?php

declare(strict_types=1);

namespace Tests\Feature\Asset;

use App\Core\Models\Household;
use App\Models\User;
use App\Modules\Asset\Actions\SeedDefaultAssetData;
use App\Modules\Asset\Enums\AssetStatus;
use App\Modules\Asset\Enums\DocumentStatus;
use App\Modules\Asset\Enums\DocumentType;
use App\Modules\Asset\Enums\IssuePriority;
use App\Modules\Asset\Enums\IssueStatus;
use App\Modules\Asset\Enums\MaintenanceTrigger;
use App\Modules\Asset\Integrations\AssetAlertSource;
use App\Modules\Asset\Integrations\AssetAttentionWidget;
use App\Modules\Asset\Integrations\AssetDocumentCalendarSource;
use App\Modules\Asset\Integrations\AssetHouseholdExporter;
use App\Modules\Asset\Integrations\AssetIssueCalendarSource;
use App\Modules\Asset\Integrations\AssetMaintenanceCalendarSource;
use App\Modules\Asset\Models\Asset;
use App\Modules\Asset\Models\AssetCategory;
use App\Modules\Asset\Models\AssetDocument;
use App\Modules\Asset\Models\Issue;
use App\Modules\Asset\Models\Location;
use App\Modules\Asset\Models\MaintenancePlan;
use App\Modules\Asset\Models\Vendor;
use App\Modules\Asset\Services\WhatsAppLinkGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class IssueVendorAndIntegrationsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Household $household;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Integration Tester',
            'email' => 'integ@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $this->household = app(\App\Core\Actions\CreateHousehold::class)->handle(
            $this->user,
            'Keluarga Integrasi',
            1,
            'Asia/Jakarta'
        );
        $this->household->onboarded_at = now();
        $this->household->save();

        app(\App\Core\Support\HouseholdContext::class)->use($this->household);

        app(SeedDefaultAssetData::class)->handle($this->household, $this->user);
    }

    public function test_whatsapp_link_generator_normalizes_number_and_embeds_message(): void
    {
        $vendor = Vendor::create([
            'household_id' => $this->household->id,
            'name' => 'Pak Joko AC',
            'whatsapp' => '0812-3456-7890',
            'services' => ['AC'],
        ]);

        $cat = AssetCategory::where('household_id', $this->household->id)->where('system_key', 'air_conditioner')->firstOrFail();
        $loc = Location::where('household_id', $this->household->id)->where('name', 'Kamar Utama')->firstOrFail();

        $asset = Asset::create([
            'household_id' => $this->household->id,
            'category_id' => $cat->id,
            'location_id' => $loc->id,
            'name' => 'AC Daikin Inverter',
            'brand' => 'Daikin',
            'model' => 'FTKQ25',
            'status' => AssetStatus::Active,
        ]);

        $url = WhatsAppLinkGenerator::generate($vendor, $this->user, $this->household, $asset);

        $this->assertNotNull($url);
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $url);
        $this->assertStringContainsString('Pak+Joko+AC', $url);
        $this->assertStringContainsString('AC+Daikin+Inverter', $url);
        $this->assertStringContainsString('Kamar+Utama', $url);
    }

    public function test_calendar_sources_produce_calendar_items(): void
    {
        $cat = AssetCategory::where('household_id', $this->household->id)->where('system_key', 'car')->firstOrFail();

        $asset = Asset::create([
            'household_id' => $this->household->id,
            'category_id' => $cat->id,
            'name' => 'Honda HR-V',
            'status' => AssetStatus::Active,
        ]);

        $targetDate = CarbonImmutable::today()->addDays(5);

        // 1. Maintenance Plan
        $plan = MaintenancePlan::create([
            'household_id' => $this->household->id,
            'asset_id' => $asset->id,
            'name' => 'Tune Up Rutin',
            'trigger' => MaintenanceTrigger::Time,
            'interval_value' => 6,
            'next_due_on' => $targetDate,
            'is_active' => true,
        ]);

        // 2. Document
        $doc = AssetDocument::create([
            'household_id' => $this->household->id,
            'asset_id' => $asset->id,
            'type' => DocumentType::VehicleTaxAnnual,
            'title' => 'Pajak STNK HR-V',
            'expires_on' => $targetDate,
            'status' => DocumentStatus::Active,
        ]);

        // 3. Issue
        $issue = Issue::create([
            'household_id' => $this->household->id,
            'title' => 'Kaki-kaki bunyi',
            'asset_id' => $asset->id,
            'priority' => IssuePriority::Urgent,
            'status' => IssueStatus::Scheduled,
            'scheduled_on' => $targetDate,
            'reported_by' => $this->user->id,
        ]);

        $maintSource = app(AssetMaintenanceCalendarSource::class);
        $docSource = app(AssetDocumentCalendarSource::class);
        $issueSource = app(AssetIssueCalendarSource::class);

        $from = CarbonImmutable::today();
        $to = CarbonImmutable::today()->addDays(10);

        $maintItems = iterator_to_array($maintSource->items($this->household, $from, $to));
        $docItems = iterator_to_array($docSource->items($this->household, $from, $to));
        $issueItems = iterator_to_array($issueSource->items($this->household, $from, $to));

        $this->assertCount(1, $maintItems);
        $this->assertStringContainsString('Tune Up Rutin', $maintItems[0]->title);

        $this->assertCount(1, $docItems);
        $this->assertStringContainsString('Pajak STNK HR-V', $docItems[0]->title);

        $this->assertCount(1, $issueItems);
        $this->assertStringContainsString('Kaki-kaki bunyi', $issueItems[0]->title);
    }

    public function test_alert_source_produces_alerts(): void
    {
        $cat = AssetCategory::where('household_id', $this->household->id)->where('system_key', 'air_conditioner')->firstOrFail();

        $asset = Asset::create([
            'household_id' => $this->household->id,
            'category_id' => $cat->id,
            'name' => 'AC Panasonic',
            'status' => AssetStatus::Active,
        ]);

        // Overdue plan
        MaintenancePlan::create([
            'household_id' => $this->household->id,
            'asset_id' => $asset->id,
            'name' => 'Cuci AC Rutin',
            'trigger' => MaintenanceTrigger::Time,
            'interval_value' => 3,
            'next_due_on' => CarbonImmutable::today()->subDays(5),
            'is_active' => true,
        ]);

        // Urgent open issue
        Issue::create([
            'household_id' => $this->household->id,
            'title' => 'Air AC bocor membanjiri kasur',
            'asset_id' => $asset->id,
            'priority' => IssuePriority::Urgent,
            'status' => IssueStatus::Open,
            'reported_by' => $this->user->id,
        ]);

        $alertSource = app(AssetAlertSource::class);
        $alerts = iterator_to_array($alertSource->alerts($this->household));

        $this->assertGreaterThanOrEqual(2, count($alerts));

        $titles = array_map(fn ($a) => $a->title, $alerts);
        $levels = array_map(fn ($a) => $a->level, $alerts);

        $this->assertContains('danger', $levels);
        $this->assertTrue(collect($titles)->contains(fn ($t) => str_contains($t, 'Cuci AC Rutin') || str_contains($t, 'terlambat')));
        $this->assertTrue(collect($titles)->contains(fn ($t) => str_contains($t, 'mendesak')));
    }

    public function test_dashboard_widget_and_household_exporter(): void
    {
        $cat = AssetCategory::where('household_id', $this->household->id)->where('system_key', 'air_conditioner')->firstOrFail();

        $asset = Asset::create([
            'household_id' => $this->household->id,
            'category_id' => $cat->id,
            'name' => 'AC Ruang Tamu',
            'status' => AssetStatus::Active,
        ]);

        $widget = app(AssetAttentionWidget::class);
        $rendered = (string) $widget->render($this->household);

        $this->assertStringContainsString('Perhatian Aset', $rendered);

        $exporter = app(AssetHouseholdExporter::class);
        $this->assertSame('asset', $exporter->module());

        $exportedData = $exporter->export($this->household);
        $this->assertArrayHasKey('assets.json', $exportedData);
        $this->assertArrayHasKey('locations.json', $exportedData);
        $this->assertArrayHasKey('categories.json', $exportedData);
        $this->assertArrayHasKey('documents.json', $exportedData);
        $this->assertArrayHasKey('maintenance_plans.json', $exportedData);
        $this->assertArrayHasKey('service_records.json', $exportedData);
        $this->assertArrayHasKey('meter_readings.json', $exportedData);
        $this->assertArrayHasKey('vendors.json', $exportedData);
        $this->assertArrayHasKey('issues.json', $exportedData);
    }

    public function test_scheduled_commands_run_successfully(): void
    {
        $this->artisan('assets:refresh-due-dates')->assertSuccessful();
        $this->artisan('assets:odometer-reminders')->assertSuccessful();
    }

    public function test_tenant_boundary_isolation_between_households(): void
    {
        // Second household
        $u2 = User::create([
            'name' => 'Tetangga User',
            'email' => 'tetangga@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);
        $h2 = app(\App\Core\Actions\CreateHousehold::class)->handle(
            $u2,
            'Keluarga Tetangga',
            1,
            'Asia/Jakarta'
        );
        $h2->onboarded_at = now();
        $h2->save();

        app(SeedDefaultAssetData::class)->handle($h2, $u2);

        $assetH2 = app(\App\Core\Support\HouseholdContext::class)->runInContext($h2, function () use ($h2) {
            $cat2 = AssetCategory::where('household_id', $h2->id)->where('system_key', 'motorcycle')->firstOrFail();

            return Asset::create([
                'household_id' => $h2->id,
                'category_id' => $cat2->id,
                'name' => 'Motor Tetangga',
                'status' => AssetStatus::Active,
            ]);
        });

        // When logged in as $this->user (household 1), accessing $assetH2 must be 404
        $this->actingAs($this->user);
        $this->get(route('assets.show', $assetH2->id))->assertNotFound();
    }
}
