<?php

declare(strict_types=1);

namespace App\Modules\Asset\Actions;

use App\Models\User;
use App\Modules\Asset\Enums\DocumentStatus;
use App\Modules\Asset\Models\AssetDocument;
use App\Modules\Finance\Contracts\FinanceGateway;
use App\Modules\Finance\DTO\ExpenseData;
use App\Modules\Finance\DTO\PlannedExpenseData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class RenewDocument
{
    public function __construct(
        private readonly FinanceGateway $financeGateway,
    ) {
    }

    /**
     * @param array{
     *     title?: string|null,
     *     number?: string|null,
     *     issuer?: string|null,
     *     issued_on?: CarbonImmutable|string|null,
     *     expires_on: CarbonImmutable|string,
     *     renewal_cost_estimate?: int|null,
     *     add_to_finance_plan?: bool,
     *     cost_paid?: int|null,
     *     account_id?: string|null,
     *     category_id?: string|null,
     *     notes?: string|null,
     * } $data
     */
    public function handle(AssetDocument $oldDoc, array $data, ?User $actor = null): AssetDocument
    {
        return DB::transaction(function () use ($oldDoc, $data, $actor) {
            // 1. Tandai dokumen lama sebagai superseded (BR-AST-050)
            $oldDoc->update([
                'status' => DocumentStatus::Superseded,
            ]);

            // Batalkan planned item dari dokumen lama jika ada
            if ($oldDoc->add_to_finance_plan) {
                try {
                    $this->financeGateway->cancelPlannedExpenses('asset_document', $oldDoc->id);
                } catch (\Throwable) {
                }
            }

            $expiresOn = is_string($data['expires_on'])
                ? CarbonImmutable::parse($data['expires_on'])
                : $data['expires_on'];

            $issuedOn = ! empty($data['issued_on'])
                ? (is_string($data['issued_on']) ? CarbonImmutable::parse($data['issued_on']) : $data['issued_on'])
                : CarbonImmutable::today();

            $addToFinance = (bool) ($data['add_to_finance_plan'] ?? $oldDoc->add_to_finance_plan);
            $renewalEstimate = isset($data['renewal_cost_estimate']) ? (int) $data['renewal_cost_estimate'] : $oldDoc->renewal_cost_estimate;

            // 2. Buat dokumen baru
            $newDoc = AssetDocument::create([
                'household_id' => $oldDoc->household_id,
                'asset_id' => $oldDoc->asset_id,
                'type' => $oldDoc->type,
                'title' => $data['title'] ?? $oldDoc->title,
                'number' => $data['number'] ?? $oldDoc->number,
                'issuer' => $data['issuer'] ?? $oldDoc->issuer,
                'issued_on' => $issuedOn,
                'expires_on' => $expiresOn,
                'remind_days' => $oldDoc->remind_days ?? [30, 7, 0],
                'status' => DocumentStatus::Active,
                'renewal_cost_estimate' => $renewalEstimate,
                'add_to_finance_plan' => $addToFinance,
                'notes' => $data['notes'] ?? $oldDoc->notes,
                'created_by' => $actor?->id,
            ]);

            // 3. Jika biaya perpanjangan dibayarkan sekarang
            $costPaid = (int) ($data['cost_paid'] ?? 0);
            if ($costPaid > 0 && ! empty($data['account_id'])) {
                $catId = $data['category_id']
                    ?? $this->financeGateway->defaultCategoryId('tax')
                    ?? $this->financeGateway->defaultCategoryId('asset_maintenance')
                    ?? '';

                if ($catId !== '') {
                    $this->financeGateway->recordExpense(new ExpenseData(
                        accountId: $data['account_id'],
                        categoryId: $catId,
                        amount: $costPaid,
                        occurredOn: CarbonImmutable::today(),
                        note: 'Perpanjangan dokumen ' . $newDoc->title,
                        sourceType: 'ast_document',
                        sourceId: $newDoc->id,
                    ));
                }
            }

            // 4. Jika masuk ke rencana keuangan untuk perpanjangan berikutnya (BR-AST-031)
            if ($addToFinance && $renewalEstimate && $renewalEstimate > 0) {
                $catId = $data['category_id']
                    ?? $this->financeGateway->defaultCategoryId('asset_maintenance')
                    ?? '';

                if ($catId !== '') {
                    $this->financeGateway->upsertPlannedExpense(new PlannedExpenseData(
                        name: "Perpanjang: {$newDoc->title}",
                        categoryId: $catId,
                        accountId: $data['account_id'] ?? null,
                        amount: $renewalEstimate,
                        dueDate: $expiresOn,
                        sourceType: 'asset_document',
                        sourceId: $newDoc->id,
                    ));
                }
            }

            return $newDoc;
        });
    }
}
