<?php

declare(strict_types=1);

namespace App\Modules\Asset\Livewire\Services;

use App\Core\Support\HouseholdContext;
use App\Modules\Asset\Actions\RecordService;
use App\Modules\Asset\Enums\ServiceType;
use App\Modules\Asset\Models\Asset;
use App\Modules\Asset\Models\Issue;
use App\Modules\Asset\Models\MaintenancePlan;
use App\Modules\Asset\Models\Vendor;
use App\Modules\Finance\Contracts\FinanceGateway;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Form extends Component
{
    public string $assetId = '';
    public ?string $planId = null;
    public ?string $issueId = null;

    public string $type = 'preventive';
    public string $performed_on = '';
    public ?int $meter_reading = null;
    public ?string $vendor_id = null;
    public ?int $cost = 0;
    public string $description = '';
    public string $parts = '';
    public ?string $service_warranty_until = null;

    // Finance options
    public bool $record_expense = true;
    public ?string $account_id = null;
    public ?string $category_id = null;

    public function mount(string $asset, ?string $plan = null, ?string $issue = null): void
    {
        $household = app(HouseholdContext::class)->currentOrNull();

        $assetRecord = Asset::where('household_id', $household?->id)->findOrFail($asset);
        $this->assetId = $assetRecord->id;
        $this->performed_on = CarbonImmutable::today()->toDateString();
        $this->meter_reading = $assetRecord->current_meter;

        if ($plan) {
            $planRecord = MaintenancePlan::where('household_id', $household?->id)->where('id', $plan)->first();
            if ($planRecord) {
                $this->planId = $planRecord->id;
                $this->description = $planRecord->name;
                $this->cost = $planRecord->estimated_cost ?? 0;
                $this->vendor_id = $planRecord->vendor_id;
            }
        }

        if ($issue) {
            $issueRecord = Issue::where('household_id', $household?->id)->where('id', $issue)->first();
            if ($issueRecord) {
                $this->issueId = $issueRecord->id;
                $this->type = 'repair';
                $this->description = 'Perbaikan: ' . $issueRecord->title;
                $this->vendor_id = $issueRecord->vendor_id;
            }
        }

        $financeGateway = app(FinanceGateway::class);
        $accountOpts = $financeGateway->accountOptions();
        if (count($accountOpts) > 0) {
            $this->account_id = array_key_first($accountOpts);
        }
    }

    public function save(RecordService $recordServiceAction): mixed
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        if (! $household) {
            return null;
        }

        $this->validate([
            'assetId' => 'required|exists:ast_assets,id',
            'type' => 'required|in:preventive,repair,inspection',
            'performed_on' => 'required|date',
            'cost' => 'nullable|integer|min:0',
            'meter_reading' => 'nullable|integer|min:0',
            'vendor_id' => 'nullable|exists:ast_vendors,id',
            'description' => 'nullable|string|max:255',
            'parts' => 'nullable|string|max:255',
            'service_warranty_until' => 'nullable|date',
        ]);

        $asset = Asset::where('household_id', $household->id)->findOrFail($this->assetId);

        $recordServiceAction->handle(
            asset: $asset,
            data: [
                'asset_id' => $asset->id,
                'maintenance_plan_id' => $this->planId ?: null,
                'issue_id' => $this->issueId ?: null,
                'type' => ServiceType::from($this->type),
                'performed_on' => $this->performed_on,
                'meter_reading' => $this->meter_reading,
                'vendor_id' => $this->vendor_id ?: null,
                'cost' => (int) ($this->cost ?? 0),
                'description' => $this->description ?: null,
                'parts' => $this->parts ?: null,
                'service_warranty_until' => $this->service_warranty_until ?: null,
                'record_expense' => $this->record_expense,
                'account_id' => $this->account_id ?: null,
                'category_id' => $this->category_id ?: null,
            ],
            actor: auth()->user(),
        );

        return redirect()->route('assets.show', ['asset' => $asset->id, 'tab' => 'history']);
    }

    public function render(FinanceGateway $financeGateway): View
    {
        $household = app(HouseholdContext::class)->currentOrNull();

        $asset = Asset::where('household_id', $household?->id)->findOrFail($this->assetId);
        $vendors = Vendor::where('household_id', $household?->id)->orderBy('name')->get();
        $accountOptions = $financeGateway->accountOptions();
        $categoryOptions = $financeGateway->expenseCategoryOptions();

        return view('modules.asset.services.form', [
            'asset' => $asset,
            'vendors' => $vendors,
            'accountOptions' => $accountOptions,
            'categoryOptions' => $categoryOptions,
        ]);
    }
}
