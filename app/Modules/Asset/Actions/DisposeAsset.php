<?php

declare(strict_types=1);

namespace App\Modules\Asset\Actions;

use App\Modules\Asset\Enums\AssetStatus;
use App\Modules\Asset\Models\Asset;
use App\Modules\Finance\Contracts\FinanceGateway;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class DisposeAsset
{
    public function __construct(
        private readonly FinanceGateway $financeGateway,
    ) {
    }

    public function handle(Asset $asset, AssetStatus $status, CarbonImmutable $disposedOn): Asset
    {
        return DB::transaction(function () use ($asset, $status, $disposedOn) {
            $asset->update([
                'status' => $status,
                'disposed_on' => $disposedOn,
            ]);

            // Nonaktifkan semua rencana perawatan dan batalkan PlannedItem di Finance (BR-AST-032)
            $plans = $asset->maintenancePlans()->where('is_active', true)->get();
            foreach ($plans as $plan) {
                $plan->update(['is_active' => false]);
                try {
                    $this->financeGateway->cancelPlannedExpenses('asset_maintenance', $plan->id);
                } catch (\Throwable) {
                }
            }

            // Batalkan PlannedItem dari dokumen aset
            $docs = $asset->documents()->where('add_to_finance_plan', true)->get();
            foreach ($docs as $doc) {
                try {
                    $this->financeGateway->cancelPlannedExpenses('asset_document', $doc->id);
                } catch (\Throwable) {
                }
            }

            return $asset;
        });
    }
}
