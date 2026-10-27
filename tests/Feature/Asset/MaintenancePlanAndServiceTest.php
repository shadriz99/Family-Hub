<?php

declare(strict_types=1);

namespace Tests\Feature\Asset;

use App\Core\Models\Household;
use App\Models\User;
use App\Modules\Asset\Actions\DeleteServiceRecord;
use App\Modules\Asset\Actions\RecordService;
use App\Modules\Asset\Actions\SeedDefaultAssetData;
use App\Modules\Asset\Enums\AssetStatus;
use App\Modules\Asset\Enums\DocumentType;
use App\Modules\Asset\Enums\IntervalUnit;
use App\Modules\Asset\Enums\MaintenanceTrigger;
use App\Modules\Asset\Enums\MeterUnit;
use App\Modules\Asset\Enums\PlanStatus;
use App\Modules\Asset\Enums\ServiceType;
use App\Modules\Asset\Models\Asset;
use App\Modules\Asset\Models\AssetCategory;
use App\Modules\Asset\Models\MaintenancePlan;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MaintenancePlanAndServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Household $household;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Maintenance Tester',
            'email' => 'maint@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $this->household = app(\App\Core\Actions\CreateHousehold::class)->handle(
            $this->user,
            'Household Maintenance',
            1,
            'Asia/Jakarta'
        );
        $this->household->onboarded_at = now();
        $this->household->save();

        app(\App\Core\Support\HouseholdContext::class)->use($this->household);

        app(SeedDefaultAssetData::class)->handle($this->household, $this->user);
    }

    public function test_next_due_date_calculation_handles_no_overflow(): void
    {
        $cat = AssetCategory::where('household_id', $this->household->id)->where('system_key', 'air_conditioner')->firstOrFail();

        $asset = Asset::create([
            'household_id' => $this->household->id,
            'category_id' => $cat->id,
            'name' => 'AC Ruang Tamu',
            'status' => AssetStatus::Active,
        ]);

        $plan = new MaintenancePlan([
            'household_id' => $this->household->id,
            'asset_id' => $asset->id,
            'name' => 'Cuci AC',
            'trigger' => MaintenanceTrigger::Time,
            'interval_value' => 1,
            'interval_unit' => IntervalUnit::Month,
        ]);

        // Base date: 31 January 2026 -> 1 month without overflow should be 28 February 2026
        $base = CarbonImmutable::create(2026, 1, 31);
        $nextDue = $plan->calculateNextDueDate($base);

        $this->assertNotNull($nextDue);
        $this->assertSame('2026-02-28', $nextDue->toDateString());
    }

    public function test_maintenance_plan_status_evaluation_and_snooze(): void
    {
        $cat = AssetCategory::where('household_id', $this->household->id)->where('system_key', 'motorcycle')->firstOrFail();

        $asset = Asset::create([
            'household_id' => $this->household->id,
            'category_id' => $cat->id,
            'name' => 'Honda Beat',
            'meter_unit' => MeterUnit::Km,
            'current_meter' => 9900,
            'status' => AssetStatus::Active,
        ]);

        $today = CarbonImmutable::create(2026, 10, 27);

        $plan = new MaintenancePlan([
            'household_id' => $this->household->id,
            'asset_id' => $asset->id,
            'name' => 'Ganti Oli',
            'trigger' => MaintenanceTrigger::TimeOrMeter,
            'interval_value' => 2,
            'interval_unit' => IntervalUnit::Month,
            'meter_interval' => 2000,
            'next_due_on' => $today->addDays(3),
            'next_due_meter' => 10000,
            'remind_days_before' => 7,
        ]);
        $plan->setRelation('asset', $asset);

        // Due date within remind_days_before (3 <= 7) -> Soon
        $this->assertSame(PlanStatus::Soon, $plan->computedStatus($today, 9900));

        // When current meter reaches next_due_meter -> Due
        $this->assertSame(PlanStatus::Due, $plan->computedStatus($today, 10000));

        // When current meter exceeds next_due_meter -> Overdue
        $this->assertSame(PlanStatus::Overdue, $plan->computedStatus($today, 10500));

        // When snoozed until 10 days later -> effective date shifts
        $plan->snoozed_until = $today->addDays(10);
        $this->assertSame('2026-11-06', $plan->effectiveDueDate()?->toDateString());
    }

    public function test_record_service_advances_plan_and_resets_snooze(): void
    {
        $cat = AssetCategory::where('household_id', $this->household->id)->where('system_key', 'motorcycle')->firstOrFail();

        $asset = Asset::create([
            'household_id' => $this->household->id,
            'category_id' => $cat->id,
            'name' => 'Honda Beat FI',
            'meter_unit' => MeterUnit::Km,
            'current_meter' => 8000,
            'status' => AssetStatus::Active,
        ]);

        $plan = MaintenancePlan::create([
            'household_id' => $this->household->id,
            'asset_id' => $asset->id,
            'name' => 'Ganti Oli Mesin',
            'trigger' => MaintenanceTrigger::Time,
            'interval_value' => 2,
            'interval_unit' => IntervalUnit::Month,
            'last_done_on' => CarbonImmutable::create(2026, 8, 1),
            'next_due_on' => CarbonImmutable::create(2026, 10, 1),
            'snoozed_until' => CarbonImmutable::create(2026, 10, 15),
            'is_active' => true,
        ]);

        $recorder = app(RecordService::class);
        $record = $recorder->handle(
            asset: $asset,
            data: [
                'asset_id' => $asset->id,
                'maintenance_plan_id' => $plan->id,
                'type' => ServiceType::Preventive,
                'performed_on' => '2026-10-27',
                'meter_reading' => 8500,
                'cost' => 85000,
                'description' => 'Ganti oli MPX2',
                'service_warranty_until' => '2026-11-27',
            ],
            actor: $this->user,
        );

        $this->assertNotNull($record->id);
        $plan->refresh();

        // BR-AST-020: last_done_on is updated to 2026-10-27
        $this->assertSame('2026-10-27', $plan->last_done_on?->toDateString());
        // BR-AST-013: snooze is reset
        $this->assertNull($plan->snoozed_until);
        // Next due on calculated 2 months after 2026-10-27 = 2026-12-27
        $this->assertSame('2026-12-27', $plan->next_due_on?->toDateString());

        // Service warranty document was created
        $this->assertNotNull($record->service_warranty_document_id);
        $record->load('warrantyDocument');
        $this->assertSame(DocumentType::Warranty, $record->warrantyDocument->type);
        $this->assertSame('2026-11-27', $record->warrantyDocument->expires_on->toDateString());
    }

    public function test_older_service_record_does_not_regress_plan_schedule(): void
    {
        $cat = AssetCategory::where('household_id', $this->household->id)->where('system_key', 'motorcycle')->firstOrFail();

        $asset = Asset::create([
            'household_id' => $this->household->id,
            'category_id' => $cat->id,
            'name' => 'Yamaha Mio',
            'status' => AssetStatus::Active,
        ]);

        $plan = MaintenancePlan::create([
            'household_id' => $this->household->id,
            'asset_id' => $asset->id,
            'name' => 'Ganti Oli',
            'trigger' => MaintenanceTrigger::Time,
            'interval_value' => 2,
            'interval_unit' => IntervalUnit::Month,
            'last_done_on' => CarbonImmutable::create(2026, 10, 1),
            'next_due_on' => CarbonImmutable::create(2026, 12, 1),
            'is_active' => true,
        ]);

        $recorder = app(RecordService::class);
        // Backfilling an old service from August
        $recorder->handle(
            asset: $asset,
            data: [
                'asset_id' => $asset->id,
                'maintenance_plan_id' => $plan->id,
                'performed_on' => '2026-08-01',
                'cost' => 50000,
            ],
            actor: $this->user,
        );

        $plan->refresh();
        // BR-AST-020: Schedule must NOT regress
        $this->assertSame('2026-10-01', $plan->last_done_on?->toDateString());
        $this->assertSame('2026-12-01', $plan->next_due_on?->toDateString());
    }

    public function test_delete_service_record_restores_plan_to_previous_state(): void
    {
        $cat = AssetCategory::where('household_id', $this->household->id)->where('system_key', 'car')->firstOrFail();

        $asset = Asset::create([
            'household_id' => $this->household->id,
            'category_id' => $cat->id,
            'name' => 'Innova Reborn',
            'status' => AssetStatus::Active,
        ]);

        $plan = MaintenancePlan::create([
            'household_id' => $this->household->id,
            'asset_id' => $asset->id,
            'name' => 'Servis Berkala',
            'trigger' => MaintenanceTrigger::Time,
            'interval_value' => 6,
            'interval_unit' => IntervalUnit::Month,
            'is_active' => true,
        ]);

        $recorder = app(RecordService::class);
        $s1 = $recorder->handle(
            asset: $asset,
            data: [
                'asset_id' => $asset->id,
                'maintenance_plan_id' => $plan->id,
                'performed_on' => '2026-04-10',
                'cost' => 500000,
            ],
            actor: $this->user,
        );

        $s2 = $recorder->handle(
            asset: $asset,
            data: [
                'asset_id' => $asset->id,
                'maintenance_plan_id' => $plan->id,
                'performed_on' => '2026-10-10',
                'cost' => 600000,
            ],
            actor: $this->user,
        );

        $plan->refresh();
        $this->assertSame('2026-10-10', $plan->last_done_on?->toDateString());

        // Delete latest service record (s2)
        $deleter = app(DeleteServiceRecord::class);
        $deleter->handle($s2);

        $plan->refresh();
        // BR-AST-021: plan schedule rolls back to s1 (2026-04-10)
        $this->assertSame('2026-04-10', $plan->last_done_on?->toDateString());
        $this->assertSame('2026-10-10', $plan->next_due_on?->toDateString());
    }
}
