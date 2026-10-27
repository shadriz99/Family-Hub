<?php

declare(strict_types=1);

namespace App\Modules\Asset\Actions;

use App\Models\User;
use App\Modules\Asset\Enums\DocumentStatus;
use App\Modules\Asset\Enums\DocumentType;
use App\Modules\Asset\Enums\IssueStatus;
use App\Modules\Asset\Enums\ServiceType;
use App\Modules\Asset\Models\Asset;
use App\Modules\Asset\Models\AssetDocument;
use App\Modules\Asset\Models\Issue;
use App\Modules\Asset\Models\MaintenancePlan;
use App\Modules\Asset\Models\ServiceRecord;
use App\Modules\Asset\Services\UpdateMeter;
use App\Modules\Finance\Contracts\FinanceGateway;
use App\Modules\Finance\DTO\ExpenseData;
use App\Modules\Finance\DTO\PlannedExpenseData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class RecordService
{
    public function __construct(
        private readonly UpdateMeter $updateMeter,
        private readonly FinanceGateway $financeGateway,
    ) {
    }

    /**
     * @param array{
     *     asset_id: string,
     *     maintenance_plan_id?: string|null,
     *     issue_id?: string|null,
     *     type?: ServiceType|string,
     *     performed_on: CarbonImmutable|string,
     *     meter_reading?: int|string|null,
     *     vendor_id?: string|null,
     *     cost?: int,
     *     description?: string|null,
     *     parts?: string|null,
     *     service_warranty_until?: CarbonImmutable|string|null,
     *     record_expense?: bool,
     *     account_id?: string|null,
     *     category_id?: string|null,
     *     planned_item_id?: string|null,
     * } $data
     */
    public function handle(Asset $asset, array $data, ?User $actor = null): ServiceRecord
    {
        return DB::transaction(function () use ($asset, $data, $actor) {
            $performedOn = is_string($data['performed_on'])
                ? CarbonImmutable::parse($data['performed_on'])
                : $data['performed_on'];

            $cost = (int) ($data['cost'] ?? 0);
            $type = isset($data['type']) && is_string($data['type'])
                ? ServiceType::from($data['type'])
                : ($data['type'] ?? ServiceType::Preventive);

            $meterReading = isset($data['meter_reading']) && $data['meter_reading'] !== ''
                ? (int) $data['meter_reading']
                : null;

            // 1. Buat catatan servis
            $record = ServiceRecord::create([
                'household_id' => $asset->household_id,
                'asset_id' => $asset->id,
                'maintenance_plan_id' => $data['maintenance_plan_id'] ?? null,
                'issue_id' => $data['issue_id'] ?? null,
                'type' => $type,
                'performed_on' => $performedOn,
                'meter_reading' => $meterReading,
                'vendor_id' => $data['vendor_id'] ?? null,
                'cost' => $cost,
                'description' => $data['description'] ?? null,
                'parts' => $data['parts'] ?? null,
                'created_by' => $actor?->id,
            ]);

            // 2. Perbarui meter jika dicatat di servis (BR-AST-002)
            if ($meterReading !== null) {
                try {
                    $this->updateMeter->handle(
                        asset: $asset,
                        rawReading: $meterReading,
                        readOn: $performedOn,
                        isReset: false,
                        source: 'service_record',
                        actor: $actor,
                    );
                } catch (\Throwable) {
                    // Jika pembacaan lebih rendah atau validasi lain, jangan batalkan pencatatan servis
                }
            }

            // 3. Garansi servis (US-AST-05 AC1)
            if (! empty($data['service_warranty_until'])) {
                $warrantyDate = is_string($data['service_warranty_until'])
                    ? CarbonImmutable::parse($data['service_warranty_until'])
                    : $data['service_warranty_until'];

                $warrantyDoc = AssetDocument::create([
                    'household_id' => $asset->household_id,
                    'asset_id' => $asset->id,
                    'type' => DocumentType::Warranty,
                    'title' => 'Garansi servis: ' . ($record->description ?: $asset->name),
                    'issued_on' => $performedOn,
                    'expires_on' => $warrantyDate,
                    'remind_days' => [30, 7, 0],
                    'status' => DocumentStatus::Active,
                    'created_by' => $actor?->id,
                ]);

                $record->update(['service_warranty_document_id' => $warrantyDoc->id]);
            }

            // 4. Update MaintenancePlan jika ada (BR-AST-020, BR-AST-013)
            $plan = null;
            if (! empty($data['maintenance_plan_id'])) {
                $plan = MaintenancePlan::where('id', $data['maintenance_plan_id'])->first();
                if ($plan) {
                    // BR-AST-020: hanya update last_done jika performed_on >= last_done_on saat ini
                    $isNewer = $plan->last_done_on === null || $performedOn->gte($plan->last_done_on);
                    if ($isNewer) {
                        $plan->last_done_on = $performedOn;
                        if ($meterReading !== null) {
                            $plan->last_done_meter = $meterReading;
                        }
                        // Reset snooze (BR-AST-013)
                        $plan->snoozed_until = null;

                        // Hitung next due date & meter berikutnya
                        $plan->next_due_on = $plan->calculateNextDueDate($performedOn);
                        if ($meterReading !== null) {
                            $plan->next_due_meter = $plan->calculateNextDueMeter($meterReading);
                        }

                        $plan->save();

                        // BR-AST-030: Upsert PlannedItem ke Finance untuk jadwal servis berikutnya
                        if ($plan->add_to_finance_plan && $plan->estimated_cost && $plan->next_due_on) {
                            $catId = $data['category_id']
                                ?? $this->financeGateway->defaultCategoryId('asset_maintenance')
                                ?? '';

                            if ($catId !== '') {
                                $this->financeGateway->upsertPlannedExpense(new PlannedExpenseData(
                                    name: "Perawatan: {$plan->name} – {$asset->name}",
                                    categoryId: $catId,
                                    accountId: $data['account_id'] ?? null,
                                    amount: $plan->estimated_cost,
                                    dueDate: $plan->next_due_on,
                                    sourceType: 'asset_maintenance',
                                    sourceId: $plan->id,
                                ));
                            }
                        }
                    }
                }
            }

            // 5. Update Issue jika ada (US-AST-05 AC4)
            if (! empty($data['issue_id'])) {
                $issue = Issue::where('id', $data['issue_id'])->first();
                if ($issue) {
                    $issue->update([
                        'status' => IssueStatus::Resolved,
                        'resolved_on' => $performedOn,
                        'resolution_note' => 'Diselesaikan melalui catatan servis',
                    ]);
                }
            }

            // 6. Pencatatan transaksi pengeluaran ke Finance (US-AST-05 AC2, AC3)
            $recordExpense = $data['record_expense'] ?? true;
            if ($recordExpense && $cost > 0 && ! empty($data['account_id'])) {
                $categoryId = $data['category_id']
                    ?? $this->financeGateway->defaultCategoryId('asset_maintenance')
                    ?? '';

                if ($categoryId !== '') {
                    $txId = $this->financeGateway->recordExpense(new ExpenseData(
                        accountId: $data['account_id'],
                        categoryId: $categoryId,
                        amount: $cost,
                        occurredOn: $performedOn,
                        note: 'Servis aset ' . $asset->name . ($record->description ? ': ' . $record->description : ''),
                        sourceType: 'ast_service_record',
                        sourceId: $record->id,
                        plannedItemId: $data['planned_item_id'] ?? null,
                    ));

                    if ($txId) {
                        $record->update(['transaction_id' => $txId]);
                    }
                }
            }

            return $record;
        });
    }
}
