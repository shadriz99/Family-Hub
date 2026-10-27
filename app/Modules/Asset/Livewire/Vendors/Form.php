<?php

declare(strict_types=1);

namespace App\Modules\Asset\Livewire\Vendors;

use App\Core\Support\HouseholdContext;
use App\Modules\Asset\Models\Vendor;
use App\Modules\Asset\Services\WhatsAppLinkGenerator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Form extends Component
{
    public ?string $vendorId = null;

    public string $name = '';

    /** @var array<string> */
    public array $services = [];
    public string $phone = '';
    public string $whatsapp = '';
    public string $address = '';
    public string $notes = '';
    public ?int $rating = 5;
    public bool $is_favorite = false;

    public function mount(?string $vendor = null): void
    {
        $household = app(HouseholdContext::class)->currentOrNull();

        if ($vendor) {
            $existing = Vendor::where('household_id', $household?->id)->where('id', $vendor)->firstOrFail();
            $this->vendorId = $existing->id;
            $this->name = $existing->name;
            $this->services = $existing->services ?? [];
            $this->phone = $existing->phone ?? '';
            $this->whatsapp = $existing->whatsapp ?? '';
            $this->address = $existing->address ?? '';
            $this->notes = $existing->notes ?? '';
            $this->rating = $existing->rating;
            $this->is_favorite = $existing->is_favorite;
        }
    }

    public function save(): mixed
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        if (! $household) {
            return null;
        }

        $this->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:25',
            'whatsapp' => 'nullable|string|max:25',
            'address' => 'nullable|string|max:255',
            'rating' => 'nullable|integer|min:1|max:5',
            'notes' => 'nullable|string',
        ]);

        $waNormalized = $this->whatsapp ? WhatsAppLinkGenerator::normalize($this->whatsapp) : null;
        $user = auth()->user();

        if ($this->vendorId) {
            $vendor = Vendor::where('household_id', $household->id)->findOrFail($this->vendorId);
            $vendor->update([
                'name' => $this->name,
                'services' => array_values(array_filter($this->services)),
                'phone' => $this->phone ?: null,
                'whatsapp' => $waNormalized ?: ($this->whatsapp ?: null),
                'address' => $this->address ?: null,
                'notes' => $this->notes ?: null,
                'rating' => $this->rating,
                'is_favorite' => $this->is_favorite,
            ]);
        } else {
            $vendor = Vendor::create([
                'household_id' => $household->id,
                'name' => $this->name,
                'services' => array_values(array_filter($this->services)),
                'phone' => $this->phone ?: null,
                'whatsapp' => $waNormalized ?: ($this->whatsapp ?: null),
                'address' => $this->address ?: null,
                'notes' => $this->notes ?: null,
                'rating' => $this->rating,
                'is_favorite' => $this->is_favorite,
                'created_by' => $user?->id,
            ]);
        }

        return redirect()->route('assets.vendors.show', $vendor->id);
    }

    public function render(): View
    {
        $serviceOptions = [
            'AC',
            'Listrik',
            'Pipa / Ledeng',
            'Bengkel Motor',
            'Bengkel Mobil',
            'Elektronik',
            'Bangunan / Renovasi',
            'Kebersihan',
            'Sedot WC',
            'Lainnya',
        ];

        return view('modules.asset.vendors.form', [
            'serviceOptions' => $serviceOptions,
        ]);
    }
}
