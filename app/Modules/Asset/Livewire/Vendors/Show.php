<?php

declare(strict_types=1);

namespace App\Modules\Asset\Livewire\Vendors;

use App\Core\Support\HouseholdContext;
use App\Modules\Asset\Models\Asset;
use App\Modules\Asset\Models\Vendor;
use App\Modules\Asset\Services\WhatsAppLinkGenerator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Show extends Component
{
    public string $vendorId;
    public ?string $selectedAssetId = null;

    public function mount(string $vendor): void
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        $record = Vendor::where('household_id', $household?->id)->findOrFail($vendor);
        $this->vendorId = $record->id;
    }

    public function render(): View
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        $user = auth()->user();

        $vendor = Vendor::with(['serviceRecords.asset'])
            ->where('household_id', $household?->id)
            ->findOrFail($this->vendorId);

        $totalSpent = $vendor->serviceRecords->sum('cost');
        $assets = Asset::where('household_id', $household?->id)->orderBy('name')->get();

        $selectedAsset = $this->selectedAssetId ? $assets->firstWhere('id', $this->selectedAssetId) : null;
        $whatsappUrl = null;
        if ($user && $household) {
            $whatsappUrl = WhatsAppLinkGenerator::generate($vendor, $user, $household, $selectedAsset);
        }

        return view('modules.asset.vendors.show', [
            'vendor' => $vendor,
            'totalSpent' => $totalSpent,
            'assets' => $assets,
            'whatsappUrl' => $whatsappUrl,
        ]);
    }
}
