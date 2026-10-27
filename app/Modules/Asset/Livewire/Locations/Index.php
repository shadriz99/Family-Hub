<?php

declare(strict_types=1);

namespace App\Modules\Asset\Livewire\Locations;

use App\Core\Support\HouseholdContext;
use App\Modules\Asset\Models\Location;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Index extends Component
{
    public string $newName = '';
    public string $newIcon = 'home';
    public ?string $editingId = null;
    public string $editName = '';

    public function createLocation(): void
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        if (! $household) {
            return;
        }

        $this->validate([
            'newName' => 'required|string|max:60',
        ]);

        $maxSort = Location::where('household_id', $household->id)->max('sort_order') ?? 0;

        Location::create([
            'household_id' => $household->id,
            'name' => trim($this->newName),
            'icon' => $this->newIcon,
            'sort_order' => $maxSort + 1,
            'created_by' => auth()->id(),
        ]);

        $this->newName = '';
    }

    public function edit(string $id): void
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        $loc = Location::where('household_id', $household?->id)->findOrFail($id);
        $this->editingId = $loc->id;
        $this->editName = $loc->name;
    }

    public function updateLocation(): void
    {
        if (! $this->editingId) {
            return;
        }

        $household = app(HouseholdContext::class)->currentOrNull();
        $loc = Location::where('household_id', $household?->id)->findOrFail($this->editingId);

        $this->validate([
            'editName' => 'required|string|max:60',
        ]);

        $loc->update(['name' => trim($this->editName)]);
        $this->editingId = null;
    }

    public function deleteLocation(string $id): void
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        $loc = Location::withCount('assets')->where('household_id', $household?->id)->findOrFail($id);

        if ($loc->assets_count > 0) {
            $this->addError('delete', 'Lokasi masih digunakan oleh ' . $loc->assets_count . ' aset.');
            return;
        }

        $loc->delete();
    }

    public function render(): View
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        $locations = Location::withCount(['assets', 'issues'])
            ->where('household_id', $household?->id)
            ->orderBy('sort_order')
            ->get();

        return view('modules.asset.locations.index', [
            'locations' => $locations,
        ]);
    }
}
