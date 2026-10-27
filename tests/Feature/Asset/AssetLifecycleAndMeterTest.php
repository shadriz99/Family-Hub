<?php

declare(strict_types=1);

namespace Tests\Feature\Asset;

use App\Core\Models\Household;
use App\Models\User;
use App\Modules\Asset\Actions\DisposeAsset;
use App\Modules\Asset\Actions\SeedDefaultAssetData;
use App\Modules\Asset\Enums\AssetStatus;
use App\Modules\Asset\Enums\MaintenanceTrigger;
use App\Modules\Asset\Enums\MeterUnit;
use App\Modules\Asset\Livewire\Assets\Form as AssetForm;
use App\Modules\Asset\Livewire\Assets\Show as AssetShow;
use App\Modules\Asset\Models\Asset;
use App\Modules\Asset\Models\AssetCategory;
use App\Modules\Asset\Models\Location;
use App\Modules\Asset\Models\MaintenancePlan;
use App\Modules\Asset\Models\MeterReading;
use App\Modules\Asset\Services\UpdateMeter;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

final class AssetLifecycleAndMeterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Household $household;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Budi Asset',
            'email' => 'budi.asset@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $this->household = app(\App\Core\Actions\CreateHousehold::class)->handle(
            $this->user,
            'Keluarga Budi',
            1,
            'Asia/Jakarta'
        );
        $this->household->onboarded_at = now();
        $this->household->save();

        app(\App\Core\Support\HouseholdContext::class)->use($this->household);

        app(SeedDefaultAssetData::class)->handle($this->household, $this->user);
    }

    public function test_can_create_vehicle_asset_with_initial_meter(): void
    {
        $cat = AssetCategory::where('household_id', $this->household->id)->where('system_key', 'motorcycle')->firstOrFail();
        $loc = Location::where('household_id', $this->household->id)->where('name', 'Garasi / Carport')->firstOrFail();

        $this->actingAs($this->user);

        Livewire::test(AssetForm::class)
            ->set('name', 'Honda Vario 160')
            ->set('category_id', $cat->id)
            ->set('location_id', $loc->id)
            ->set('plate_number', 'b 1234 xyz')
            ->set('initial_meter', 15000)
            ->set('meter_unit', 'km')
            ->call('save')
            ->assertRedirect();

        $asset = Asset::where('household_id', $this->household->id)->where('name', 'Honda Vario 160')->first();
        $this->assertNotNull($asset);
        $this->assertSame('B 1234 XYZ', $asset->plate_number);
        $this->assertSame(15000, $asset->current_meter);

        // MeterReading entry was created
        $this->assertDatabaseHas('ast_meter_readings', [
            'asset_id' => $asset->id,
            'raw_reading' => 15000,
            'reading' => 15000,
            'cumulative_offset' => 0,
        ]);
    }

    public function test_update_meter_rejects_lower_physical_reading_without_reset(): void
    {
        $cat = AssetCategory::where('household_id', $this->household->id)->where('system_key', 'motorcycle')->firstOrFail();

        $asset = Asset::create([
            'household_id' => $this->household->id,
            'category_id' => $cat->id,
            'name' => 'Yamaha NMAX',
            'meter_unit' => MeterUnit::Km,
            'status' => AssetStatus::Active,
        ]);

        $service = app(UpdateMeter::class);
        $service->handle($asset, 20000, CarbonImmutable::today()->subMonths(1));

        $asset->refresh();
        $this->assertSame(20000, $asset->current_meter);

        // Lower reading without reset must throw ValidationException (BR-AST-003)
        $this->expectException(ValidationException::class);
        $service->handle($asset, 18000, CarbonImmutable::today(), isReset: false);
    }

    public function test_update_meter_reset_maintains_cumulative_monotonic_increase(): void
    {
        $cat = AssetCategory::where('household_id', $this->household->id)->where('system_key', 'motorcycle')->firstOrFail();

        $asset = Asset::create([
            'household_id' => $this->household->id,
            'category_id' => $cat->id,
            'name' => 'Yamaha Aerox',
            'meter_unit' => MeterUnit::Km,
            'status' => AssetStatus::Active,
        ]);

        $service = app(UpdateMeter::class);
        // Initial reading 50,000 km
        $service->handle($asset, 50000, CarbonImmutable::today()->subMonths(2));

        // Speedometer replaced with new one showing 500 km (reset = true)
        // BR-AST-003: offset = max(50000 - 500, 0) = 49500
        // Cumulative reading = 500 + 49500 = 50000
        $r2 = $service->handle($asset, 500, CarbonImmutable::today()->subMonth(), isReset: true);
        $this->assertSame(49500, $r2->cumulative_offset);
        $this->assertSame(50000, $r2->reading);

        // Later, reading advances to 2,000 km on new speedometer
        $r3 = $service->handle($asset, 2000, CarbonImmutable::today(), isReset: false);
        $this->assertSame(49500, $r3->cumulative_offset);
        $this->assertSame(51500, $r3->reading);

        $asset->refresh();
        $this->assertSame(51500, $asset->current_meter);
    }

    public function test_dispose_asset_deactivates_maintenance_plans(): void
    {
        $cat = AssetCategory::where('household_id', $this->household->id)->where('system_key', 'car')->firstOrFail();

        $asset = Asset::create([
            'household_id' => $this->household->id,
            'category_id' => $cat->id,
            'name' => 'Toyota Avanza',
            'status' => AssetStatus::Active,
        ]);

        $plan = MaintenancePlan::create([
            'household_id' => $this->household->id,
            'asset_id' => $asset->id,
            'name' => 'Ganti Oli',
            'trigger' => MaintenanceTrigger::Time,
            'interval_value' => 6,
            'is_active' => true,
        ]);

        $this->assertTrue($plan->is_active);

        $disposer = app(DisposeAsset::class);
        $disposer->handle($asset, AssetStatus::Sold, CarbonImmutable::today());

        $asset->refresh();
        $plan->refresh();

        $this->assertSame(AssetStatus::Sold, $asset->status);
        $this->assertFalse($plan->is_active);
    }

    public function test_asset_show_component_renders_and_handles_meter_update(): void
    {
        $cat = AssetCategory::where('household_id', $this->household->id)->where('system_key', 'motorcycle')->firstOrFail();

        $asset = Asset::create([
            'household_id' => $this->household->id,
            'category_id' => $cat->id,
            'name' => 'Vespa Sprint',
            'meter_unit' => MeterUnit::Km,
            'current_meter' => 5000,
            'status' => AssetStatus::Active,
        ]);

        $this->actingAs($this->user);

        Livewire::test(AssetShow::class, ['asset' => $asset->id])
            ->assertSee('Vespa Sprint')
            ->assertSee('5.000 km')
            ->set('meterInput', 5500)
            ->set('meterDate', CarbonImmutable::today()->toDateString())
            ->call('saveMeter')
            ->assertHasNoErrors();

        $asset->refresh();
        $this->assertSame(5500, $asset->current_meter);
    }
}
