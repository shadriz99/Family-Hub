<?php

declare(strict_types=1);

namespace App\Modules\Asset\Livewire\Maintenance;

use App\Core\Support\HouseholdContext;
use App\Modules\Asset\Enums\IntervalUnit;
use App\Modules\Asset\Enums\MaintenanceTrigger;
use App\Modules\Asset\Models\Asset;
use App\Modules\Asset\Models\MaintenancePlan;
use App\Modules\Asset\Models\Vendor;
use App\Modules\Finance\Contracts\FinanceGateway;
use App\Modules\Finance\DTO\PlannedExpenseData;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Form extends Component
{
    public ?string $planId = null;
    public string $assetId = '';

    public string $name = '';
    public string $trigger = 'time';
    public ?int $interval_value = 3;
    public string $interval_unit = 'month';
    public ?int $meter_interval = null;
    public ?string $last_done_on = null;
    public ?int $last_done_meter = null;
    public ?int $estimated_cost = null;
    public ?string $vendor_id = null;
    public int $remind_days_before = 7;
    public bool $add_to_finance_plan = false;
    public string $instructions = '';

    public function mount(?string $asset = null, ?string $plan = null): void
    {
        $household = app(HouseholdContext::class)->currentOrNull();

        if ($plan) {
            $existing = MaintenancePlan::where('household_id', $household?->id)->where('id', $plan)->firstOrFail();
            $this->planId = $existing->id;
            $this->assetId = $existing->asset_id;
            $this->name = $existing->name;
            $this->trigger = $existing->trigger->value;
            $this->interval_value = $existing->interval_value;
            $this->interval_unit = $existing->interval_unit->value ?? 'month';
            $this->meter_interval = $existing->meter_interval;
            $this->last_done_on = $existing->last_done_on?->toDateString();
            $this->last_done_meter = $existing->last_done_meter;
            $this->estimated_cost = $existing->estimated_cost;
            $this->vendor_id = $existing->vendor_id;
            $this->remind_days_before = $existing->remind_days_before;
            $this->add_to_finance_plan = $existing->add_to_finance_plan;
            $this->instructions = $existing->instructions ?? '';
        } elseif ($asset) {
            $this->assetId = $asset;
        }
    }

    public function save(FinanceGateway $financeGateway): mixed
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        if (! $household) {
            return null;
        }

        $this->validate([
            'assetId' => 'required|exists:ast_assets,id',
            'name' => 'required|string|max:100',
            'trigger' => 'required|in:time,meter,time_or_meter',
            'interval_value' => 'nullable|integer|min:1',
            'interval_unit' => 'nullable|in:day,week,month,year',
            'meter_interval' => 'nullable|integer|min:10',
            'estimated_cost' => 'nullable|integer|min:0',
            'vendor_id' => 'nullable|exists:ast_vendors,id',
            'remind_days_before' => 'integer|min:0|max:90',
        ]);

        $asset = Asset::where('household_id', $household->id)->findOrFail($this->assetId);
        $user = auth()->user();

        $trigger = MaintenanceTrigger::from($this->trigger);
        $intUnit = $this->interval_unit ? IntervalUnit::from($this->interval_unit) : null;
        $lastDoneDate = $this->last_done_on ? CarbonImmutable::parse($this->last_done_on) : null;

        if ($this->planId) {
            $plan = MaintenancePlan::where('household_id', $household->id)->findOrFail($this->planId);
            $plan->update([
                'name' => $this->name,
                'trigger' => $trigger,
                'interval_value' => $this->interval_value,
                'interval_unit' => $intUnit,
                'meter_interval' => $this->meter_interval,
                'last_done_on' => $lastDoneDate,
                'last_done_meter' => $this->last_done_meter,
                'estimated_cost' => $this->estimated_cost,
                'vendor_id' => $this->vendor_id ?: null,
                'remind_days_before' => $this->remind_days_before,
                'add_to_finance_plan' => $this->add_to_finance_plan,
                'instructions' => $this->instructions ?: null,
            ]);
        } else {
            $plan = new MaintenancePlan([
                'household_id' => $household->id,
                'asset_id' => $asset->id,
                'name' => $this->name,
                'trigger' => $trigger,
                'interval_value' => $this->interval_value,
                'interval_unit' => $intUnit,
                'meter_interval' => $this->meter_interval,
                'last_done_on' => $lastDoneDate,
                'last_done_meter' => $this->last_done_meter,
                'estimated_cost' => $this->estimated_cost,
                'vendor_id' => $this->vendor_id ?: null,
                'remind_days_before' => $this->remind_days_before,
                'add_to_finance_plan' => $this->add_to_finance_plan,
                'instructions' => $this->instructions ?: null,
                'is_active' => true,
                'created_by' => $user?->id,
            ]);
        }

        // Calculate next due on & meter
        $plan->next_due_on = $plan->calculateNextDueDate($lastDoneDate);
        $plan->next_due_meter = $plan->calculateNextDueMeter($this->last_done_meter ?? $asset->current_meter ?? 0);
        $plan->save();

        // BR-AST-030: Upsert or cancel PlannedItem in Finance
        if ($this->add_to_finance_plan && $plan->estimated_cost && $plan->next_due_on) {
            $catId = $financeGateway->defaultCategoryId('asset_maintenance') ?? '';
            if ($catId !== '') {
                $financeGateway->upsertPlannedExpense(new PlannedExpenseData(
                    name: "Perawatan: {$plan->name} – {$asset->name}",
                    categoryId: $catId,
                    accountId: null,
                    amount: $plan->estimated_cost,
                    dueDate: $plan->next_due_on,
                    sourceType: 'asset_maintenance',
                    sourceId: $plan->id,
                ));
            }
        } elseif (! $this->add_to_finance_plan && $this->planId) {
            try {
                $financeGateway->cancelPlannedExpenses('asset_maintenance', $plan->id);
            } catch (\Throwable) {
            }
        }

        return redirect()->route('assets.show', ['asset' => $asset->id, 'tab' => 'maintenance']);
    }

    public function render(): View
    {
        $household = app(HouseholdContext::class)->currentOrNull();

        $asset = Asset::where('household_id', $household?->id)->findOrFail($this->assetId);
        $vendors = Vendor::where('household_id', $household?->id)->orderBy('name')->get();

        return view('modules.asset.maintenance.form', [
            'asset' => $asset,
            'vendors' => $vendors,
        ])->layout('layouts.app', ['title' => $this->planId ? 'Edit Jadwal Perawatan' : 'Tambah Jadwal Perawatan']);
    }
}
