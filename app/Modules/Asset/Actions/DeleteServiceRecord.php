<?php

declare(strict_types=1);

namespace App\Modules\Asset\Actions;

use App\Modules\Asset\Models\MaintenancePlan;
use App\Modules\Asset\Models\ServiceRecord;
use App\Modules\Finance\Contracts\FinanceGateway;
use Illuminate\Support\Facades\DB;

final class DeleteServiceRecord
{
    public function __construct(
        private readonly FinanceGateway $financeGateway,
    ) {
    }

    public function handle(ServiceRecord $record, bool $deleteExpenseTransaction = true): void
    {
        DB::transaction(function () use ($record, $deleteExpenseTransaction) {
            $planId = $record->maintenance_plan_id;
            $txId = $record->transaction_id;

            // 1. Hapus transaksi keuangan jika ada
            if ($deleteExpenseTransaction && $txId) {
                try {
                    $this->financeGateway->deleteExpense($txId);
                } catch (\Throwable) {
                    // Abaikan jika finance disabled / null gateway
                }
            }

            // 2. Soft delete service record
            $record->delete();

            // 3. Hitung ulang rencana perawatan dari catatan tersisa (BR-AST-021)
            if ($planId) {
                $plan = MaintenancePlan::find($planId);
                if ($plan) {
                    $latestRemaining = ServiceRecord::where('maintenance_plan_id', $planId)
                        ->orderByDesc('performed_on')
                        ->first();

                    if ($latestRemaining) {
                        $plan->last_done_on = $latestRemaining->performed_on;
                        $plan->last_done_meter = $latestRemaining->meter_reading;
                        $plan->next_due_on = $plan->calculateNextDueDate($latestRemaining->performed_on);
                        if ($latestRemaining->meter_reading !== null) {
                            $plan->next_due_meter = $plan->calculateNextDueMeter($latestRemaining->meter_reading);
                        }
                    } else {
                        $plan->last_done_on = null;
                        $plan->last_done_meter = null;
                        $plan->next_due_on = $plan->calculateNextDueDate(null);
                        $plan->next_due_meter = $plan->calculateNextDueMeter(null);
                    }

                    $plan->save();
                }
            }
        });
    }
}
