<?php

declare(strict_types=1);

namespace App\Modules\Asset\Commands;

use App\Core\Models\Household;
use App\Core\Support\HouseholdContext;
use App\Modules\Asset\Enums\MaintenanceTrigger;
use App\Modules\Asset\Models\MaintenancePlan;
use App\Modules\Finance\Contracts\FinanceGateway;
use App\Modules\Finance\DTO\PlannedExpenseData;
use Illuminate\Console\Command;
use Throwable;

final class RefreshAssetDueDatesCommand extends Command
{
    protected $signature = 'assets:refresh-due-dates';
    protected $description = 'Refresh maintenance next due dates and synchronize planned finance expenses';

    public function handle(HouseholdContext $context, FinanceGateway $financeGateway): int
    {
        $this->info('Refreshing asset maintenance due dates...');

        $households = Household::all();

        foreach ($households as $household) {
            $context->runInContext($household, function () use ($household, $financeGateway): void {
                try {
                    $plans = MaintenancePlan::query()
                        ->with('asset')
                        ->where('household_id', $household->id)
                        ->where('is_active', true)
                        ->get();

                    foreach ($plans as $plan) {
                        $changed = false;

                        // Defensively recalculate next_due_on if null
                        if ($plan->next_due_on === null && in_array($plan->trigger, [MaintenanceTrigger::Time, MaintenanceTrigger::TimeOrMeter], true)) {
                            $plan->next_due_on = $plan->calculateNextDueDate();
                            $changed = true;
                        }

                        if ($plan->next_due_meter === null && in_array($plan->trigger, [MaintenanceTrigger::Meter, MaintenanceTrigger::TimeOrMeter], true)) {
                            $plan->next_due_meter = $plan->calculateNextDueMeter();
                            $changed = true;
                        }

                        if ($changed) {
                            $plan->save();
                        }

                        // Sync planned finance item
                        if ($plan->add_to_finance_plan && $plan->estimated_cost && $plan->next_due_on) {
                            $catId = $financeGateway->defaultCategoryId('asset_maintenance') ?? '';
                            if ($catId !== '') {
                                $assetName = $plan->asset->name ?? 'Aset';
                                $financeGateway->upsertPlannedExpense(new PlannedExpenseData(
                                    name: "Perawatan: {$plan->name} – {$assetName}",
                                    categoryId: $catId,
                                    accountId: null,
                                    amount: $plan->estimated_cost,
                                    dueDate: $plan->next_due_on,
                                    sourceType: 'asset_maintenance',
                                    sourceId: $plan->id,
                                ));
                            }
                        }
                    }
                } catch (Throwable $e) {
                    $this->error("Failed refreshing for household {$household->id}: {$e->getMessage()}");
                }
            });
        }

        $this->info('Asset due dates refreshed successfully.');

        return self::SUCCESS;
    }
}
