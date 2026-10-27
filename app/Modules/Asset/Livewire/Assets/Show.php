<?php

declare(strict_types=1);

namespace App\Modules\Asset\Livewire\Assets;

use App\Core\Support\HouseholdContext;
use App\Modules\Asset\Actions\DeleteServiceRecord;
use App\Modules\Asset\Actions\DisposeAsset;
use App\Modules\Asset\Enums\AssetStatus;
use App\Modules\Asset\Models\Asset;
use App\Modules\Asset\Models\MaintenancePlan;
use App\Modules\Asset\Models\ServiceRecord;
use App\Modules\Asset\Services\UpdateMeter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
final class Show extends Component
{
    public string $assetId;

    #[Url]
    public string $tab = 'summary'; // summary, maintenance, history, documents, issues

    // Modal Update Meter
    public bool $showMeterModal = false;
    public ?int $meterInput = null;
    public string $meterDate = '';
    public bool $meterIsReset = false;

    // Modal Snooze Maintenance
    public bool $showSnoozeModal = false;
    public ?string $snoozePlanId = null;
    public ?int $snoozeDays = 7;
    public string $snoozeReason = '';

    // Modal Dispose Asset
    public bool $showDisposeModal = false;
    public string $disposeStatus = 'sold';
    public string $disposeDate = '';

    public function mount(string $asset): void
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        $record = Asset::where('household_id', $household?->id)->where('id', $asset)->firstOrFail();
        $this->assetId = $record->id;
        $this->meterDate = CarbonImmutable::today()->toDateString();
        $this->disposeDate = CarbonImmutable::today()->toDateString();
    }

    public function openMeterModal(): void
    {
        $asset = $this->getAsset();
        $this->meterInput = $asset->current_meter;
        $this->meterDate = CarbonImmutable::today()->toDateString();
        $this->meterIsReset = false;
        $this->showMeterModal = true;
    }

    public function saveMeter(UpdateMeter $updateMeter): void
    {
        $asset = $this->getAsset();
        if ($this->meterInput === null || $this->meterInput < 0) {
            $this->addError('meterInput', 'Angka meter wajib diisi.');
            return;
        }

        try {
            $updateMeter->handle(
                asset: $asset,
                rawReading: (int) $this->meterInput,
                readOn: CarbonImmutable::parse($this->meterDate),
                isReset: $this->meterIsReset,
                source: 'manual',
                actor: auth()->user(),
            );

            $this->showMeterModal = false;
        } catch (\Throwable $e) {
            $this->addError('meterInput', $e->getMessage());
        }
    }

    public function openSnoozeModal(string $planId): void
    {
        $this->snoozePlanId = $planId;
        $this->snoozeDays = 7;
        $this->snoozeReason = '';
        $this->showSnoozeModal = true;
    }

    public function saveSnooze(): void
    {
        if (! $this->snoozePlanId) {
            return;
        }

        $plan = MaintenancePlan::where('id', $this->snoozePlanId)->first();
        if ($plan) {
            $base = $plan->effectiveDueDate() ?? CarbonImmutable::today();
            $plan->snoozed_until = $base->addDays($this->snoozeDays ?? 7);
            $plan->save();
        }

        $this->showSnoozeModal = false;
    }

    public function openDisposeModal(): void
    {
        $this->disposeStatus = 'sold';
        $this->disposeDate = CarbonImmutable::today()->toDateString();
        $this->showDisposeModal = true;
    }

    public function saveDispose(DisposeAsset $disposeAsset): void
    {
        $asset = $this->getAsset();
        $disposeAsset->handle(
            asset: $asset,
            status: AssetStatus::from($this->disposeStatus),
            disposedOn: CarbonImmutable::parse($this->disposeDate),
        );

        $this->showDisposeModal = false;
    }

    public function deleteService(string $serviceRecordId, DeleteServiceRecord $deleteServiceRecord): void
    {
        $service = ServiceRecord::where('id', $serviceRecordId)->where('asset_id', $this->assetId)->first();
        if ($service) {
            $deleteServiceRecord->handle($service, deleteExpenseTransaction: true);
        }
    }

    private function getAsset(): Asset
    {
        $household = app(HouseholdContext::class)->currentOrNull();

        return Asset::query()
            ->with(['category', 'location', 'maintenancePlans.vendor', 'documents', 'serviceRecords.vendor', 'issues.reporter'])
            ->where('household_id', $household?->id)
            ->findOrFail($this->assetId);
    }

    public function render(): View
    {
        $asset = $this->getAsset();

        // Calculate total service costs
        $now = CarbonImmutable::now();
        $oneYearAgo = $now->subYear();

        $allServiceRecords = $asset->serviceRecords;
        $totalCostAllTime = $allServiceRecords->sum('cost');
        $totalCostPastYear = $allServiceRecords
            ->filter(fn (ServiceRecord $r) => $r->performed_on->gte($oneYearAgo))
            ->sum('cost');

        return view('modules.asset.assets.show', [
            'asset' => $asset,
            'totalCostAllTime' => $totalCostAllTime,
            'totalCostPastYear' => $totalCostPastYear,
        ]);
    }
}
