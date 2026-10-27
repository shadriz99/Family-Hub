<?php

declare(strict_types=1);

namespace Tests\Feature\Asset;

use App\Core\Models\Household;
use App\Models\User;
use App\Modules\Asset\Actions\SeedDefaultAssetData;
use App\Modules\Asset\Enums\AssetStatus;
use App\Modules\Asset\Enums\DocumentStatus;
use App\Modules\Asset\Enums\DocumentType;
use App\Modules\Asset\Enums\IntervalUnit;
use App\Modules\Asset\Enums\IssuePriority;
use App\Modules\Asset\Enums\IssueStatus;
use App\Modules\Asset\Enums\MaintenanceTrigger;
use App\Modules\Asset\Enums\MeterUnit;
use App\Modules\Asset\Enums\PlanStatus;
use App\Modules\Asset\Enums\ServiceType;
use App\Modules\Asset\Models\Asset;
use App\Modules\Asset\Models\AssetCategory;
use App\Modules\Asset\Models\AssetDocument;
use App\Modules\Asset\Models\Location;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class AssetSchemaAndModelsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Household $household;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Asset User',
            'email' => 'asset@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $this->household = app(\App\Core\Actions\CreateHousehold::class)->handle(
            $this->user,
            'Asset Family',
            1,
            'Asia/Jakarta'
        );
        $this->household->onboarded_at = now();
        $this->household->save();

        app(\App\Core\Support\HouseholdContext::class)->use($this->household);
    }

    public function test_all_asset_database_tables_exist(): void
    {
        $tables = [
            'ast_locations',
            'ast_categories',
            'ast_assets',
            'ast_documents',
            'ast_vendors',
            'ast_maintenance_plans',
            'ast_issues',
            'ast_service_records',
            'ast_meter_readings',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table {$table} should exist in schema.");
        }
    }

    public function test_asset_module_flag_is_enabled(): void
    {
        $this->assertTrue(config('modules.asset'));
    }

    public function test_asset_enums_have_proper_labels_and_values(): void
    {
        $this->assertSame('active', AssetStatus::Active->value);
        $this->assertSame('Aktif', AssetStatus::Active->label());
        $this->assertSame('km', MeterUnit::Km->value);
        $this->assertSame('warranty', DocumentType::Warranty->value);
        $this->assertSame('active', DocumentStatus::Active->value);
        $this->assertSame('time', MaintenanceTrigger::Time->value);
        $this->assertSame('month', IntervalUnit::Month->value);
        $this->assertSame('urgent', IssuePriority::Urgent->value);
        $this->assertSame('open', IssueStatus::Open->value);
        $this->assertSame('overdue', PlanStatus::Overdue->value);
        $this->assertSame('preventive', ServiceType::Preventive->value);
    }

    public function test_seeding_default_locations_and_categories_via_action_and_event(): void
    {
        $freshUser = User::create([
            'name' => 'Fresh User',
            'email' => 'fresh@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $freshHousehold = Household::create([
            'name' => 'Fresh Family',
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'onboarded_at' => now(),
        ]);

        $seeder = new SeedDefaultAssetData();
        $res = $seeder->handle($freshHousehold, $freshUser);

        $this->assertGreaterThanOrEqual(10, $res['locations']);
        $this->assertGreaterThanOrEqual(13, $res['categories']);

        $this->assertDatabaseHas('ast_locations', [
            'household_id' => $freshHousehold->id,
            'name' => 'Dapur',
        ]);

        $this->assertDatabaseHas('ast_categories', [
            'household_id' => $freshHousehold->id,
            'system_key' => 'air_conditioner',
        ]);

        // Second run should be idempotent
        $res2 = $seeder->handle($freshHousehold, $freshUser);
        $this->assertSame(0, $res2['locations']);
        $this->assertSame(0, $res2['categories']);
    }

    public function test_asset_and_relations_work_properly(): void
    {
        app(SeedDefaultAssetData::class)->handle($this->household, $this->user);

        $cat = AssetCategory::where('household_id', $this->household->id)->where('system_key', 'air_conditioner')->firstOrFail();
        $loc = Location::where('household_id', $this->household->id)->where('name', 'Kamar Utama')->firstOrFail();

        $asset = Asset::create([
            'household_id' => $this->household->id,
            'category_id' => $cat->id,
            'location_id' => $loc->id,
            'name' => 'AC Daikin 1 PK',
            'brand' => 'Daikin',
            'model' => 'FTV25CXV',
            'status' => AssetStatus::Active,
            'purchase_price' => 4500000,
            'created_by' => $this->user->id,
        ]);

        $this->assertNotNull($asset->id);
        $this->assertSame('AC Daikin 1 PK', $asset->name);
        $this->assertSame($cat->id, $asset->category->id);
        $this->assertSame($loc->id, $asset->location->id);

        $doc = AssetDocument::create([
            'household_id' => $this->household->id,
            'asset_id' => $asset->id,
            'type' => DocumentType::Warranty,
            'title' => 'Garansi Kompresor 3 Tahun',
            'issued_on' => CarbonImmutable::today(),
            'expires_on' => CarbonImmutable::today()->addYears(3),
            'status' => DocumentStatus::Active,
        ]);

        $this->assertCount(1, $asset->documents);
        $this->assertSame($doc->id, $asset->documents->first()->id);
    }
}
