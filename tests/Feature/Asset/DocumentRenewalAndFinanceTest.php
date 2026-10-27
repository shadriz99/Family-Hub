<?php

declare(strict_types=1);

namespace Tests\Feature\Asset;

use App\Core\Models\Household;
use App\Models\User;
use App\Modules\Asset\Actions\DeleteServiceRecord;
use App\Modules\Asset\Actions\RecordService;
use App\Modules\Asset\Actions\RenewDocument;
use App\Modules\Asset\Actions\SeedDefaultAssetData;
use App\Modules\Asset\Enums\AssetStatus;
use App\Modules\Asset\Enums\DocumentStatus;
use App\Modules\Asset\Enums\DocumentType;
use App\Modules\Asset\Enums\ServiceType;
use App\Modules\Asset\Models\Asset;
use App\Modules\Asset\Models\AssetCategory;
use App\Modules\Asset\Models\AssetDocument;
use App\Modules\Finance\Contracts\FinanceGateway;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DocumentRenewalAndFinanceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Household $household;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Finance Asset Tester',
            'email' => 'fin.asset@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $this->household = app(\App\Core\Actions\CreateHousehold::class)->handle(
            $this->user,
            'Keluarga Mandiri',
            1,
            'Asia/Jakarta'
        );
        $this->household->onboarded_at = now();
        $this->household->save();

        app(\App\Core\Support\HouseholdContext::class)->use($this->household);

        app(SeedDefaultAssetData::class)->handle($this->household, $this->user);
    }

    public function test_document_renewal_marks_old_document_superseded(): void
    {
        $cat = AssetCategory::where('household_id', $this->household->id)->where('system_key', 'motorcycle')->firstOrFail();

        $asset = Asset::create([
            'household_id' => $this->household->id,
            'category_id' => $cat->id,
            'name' => 'Honda PCX',
            'status' => AssetStatus::Active,
        ]);

        $oldDoc = AssetDocument::create([
            'household_id' => $this->household->id,
            'asset_id' => $asset->id,
            'type' => DocumentType::VehicleTaxAnnual,
            'title' => 'Pajak STNK Tahunan Honda PCX',
            'number' => '1234567/STNK',
            'issuer' => 'Samsat Jakarta',
            'issued_on' => CarbonImmutable::create(2025, 10, 20),
            'expires_on' => CarbonImmutable::create(2026, 10, 20),
            'status' => DocumentStatus::Active,
            'renewal_cost_estimate' => 380000,
            'add_to_finance_plan' => true,
        ]);

        $renewer = app(RenewDocument::class);
        $newDoc = $renewer->handle(
            oldDoc: $oldDoc,
            data: [
                'expires_on' => '2027-10-20',
                'cost_paid' => 380000,
                'renewal_cost_estimate' => 400000,
                'add_to_finance_plan' => true,
            ],
            actor: $this->user,
        );

        $oldDoc->refresh();

        // BR-AST-050: old doc becomes superseded
        $this->assertSame(DocumentStatus::Superseded, $oldDoc->status);
        $this->assertSame(DocumentStatus::Active, $newDoc->status);
        $this->assertSame('2027-10-20', $newDoc->expires_on?->toDateString());
        $this->assertSame(400000, $newDoc->renewal_cost_estimate);
    }

    public function test_service_record_with_mocked_finance_gateway_records_and_deletes_expense(): void
    {
        $cat = AssetCategory::where('household_id', $this->household->id)->where('system_key', 'motorcycle')->firstOrFail();

        $asset = Asset::create([
            'household_id' => $this->household->id,
            'category_id' => $cat->id,
            'name' => 'Honda Scoopy',
            'status' => AssetStatus::Active,
        ]);

        // Mock FinanceGateway
        $mockGateway = $this->createMock(FinanceGateway::class);
        $mockGateway->expects($this->once())
            ->method('recordExpense')
            ->willReturn('tx-mock-12345');

        $mockGateway->expects($this->once())
            ->method('deleteExpense')
            ->with('tx-mock-12345');

        $recorder = new RecordService(
            updateMeter: app(\App\Modules\Asset\Services\UpdateMeter::class),
            financeGateway: $mockGateway,
        );

        $serviceRecord = $recorder->handle(
            asset: $asset,
            data: [
                'asset_id' => $asset->id,
                'type' => ServiceType::Preventive,
                'performed_on' => '2026-10-27',
                'cost' => 120000,
                'description' => 'Servis berkala Scoopy',
                'record_expense' => true,
                'account_id' => 'acc-mock-1',
                'category_id' => 'cat-mock-1',
            ],
            actor: $this->user,
        );

        $this->assertSame('tx-mock-12345', $serviceRecord->transaction_id);

        $deleter = new DeleteServiceRecord($mockGateway);
        $deleter->handle($serviceRecord, deleteExpenseTransaction: true);

        $this->assertSoftDeleted('ast_service_records', ['id' => $serviceRecord->id]);
    }
}
