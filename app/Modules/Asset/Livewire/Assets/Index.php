<?php

declare(strict_types=1);

namespace App\Modules\Asset\Livewire\Assets;

use App\Core\Support\HouseholdContext;
use App\Modules\Asset\Enums\PlanStatus;
use App\Modules\Asset\Models\Asset;
use App\Modules\Asset\Models\AssetCategory;
use App\Modules\Asset\Models\Location;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
final class Index extends Component
{
    #[Url]
    public string $search = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $location = '';

    #[Url]
    public string $status = 'active';

    #[Url]
    public bool $needsAttentionOnly = false;

    #[Url]
    public string $groupBy = 'location'; // 'location' or 'category'

    public function render(): View
    {
        $household = app(HouseholdContext::class)->currentOrNull();

        $query = Asset::query()
            ->with(['category', 'location', 'maintenancePlans', 'documents', 'issues'])
            ->where('household_id', $household?->id);

        if ($this->search !== '') {
            $s = '%' . trim($this->search) . '%';
            $query->where(function (Builder $q) use ($s): void {
                $q->where('name', 'like', $s)
                    ->orWhere('brand', 'like', $s)
                    ->orWhere('model', 'like', $s)
                    ->orWhere('serial_number', 'like', $s)
                    ->orWhere('plate_number', 'like', $s);
            });
        }

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        if ($this->category !== '') {
            $query->where('category_id', $this->category);
        }

        if ($this->location !== '') {
            $query->where('location_id', $this->location);
        }

        $allAssets = $query->get();

        if ($this->needsAttentionOnly) {
            $allAssets = $allAssets->filter(function (Asset $asset): bool {
                $status = $asset->urgentMaintenanceStatus();
                $hasUrgentMaintenance = in_array($status, [PlanStatus::Overdue, PlanStatus::Due, PlanStatus::Soon], true);
                $hasExpiringDocs = $asset->documents->contains(fn ($doc) => $doc->expires_on && $doc->expires_on->diffInDays(now(), false) <= 30);
                $hasOpenIssues = $asset->issues->contains(fn ($iss) => in_array($iss->status->value, ['open', 'scheduled', 'in_progress'], true));

                return $hasUrgentMaintenance || $hasExpiringDocs || $hasOpenIssues;
            });
        }

        // Grouping
        $groupedAssets = match ($this->groupBy) {
            'category' => $allAssets->groupBy(fn (Asset $a) => $a->category->name ?? 'Tanpa Kategori'),
            default => $allAssets->groupBy(fn (Asset $a) => $a->location->name ?? 'Tanpa Lokasi'),
        };

        $categories = AssetCategory::query()
            ->where('household_id', $household?->id)
            ->orderBy('name')
            ->get();

        $locations = Location::query()
            ->where('household_id', $household?->id)
            ->orderBy('sort_order')
            ->get();

        // Favorite vehicle for quick meter update FAB
        $favoriteVehicle = $allAssets->first(fn (Asset $a) => $a->is_favorite && $a->meter_unit !== null);

        return view('modules.asset.assets.index', [
            'groupedAssets' => $groupedAssets,
            'categories' => $categories,
            'locations' => $locations,
            'totalCount' => $allAssets->count(),
            'favoriteVehicle' => $favoriteVehicle,
        ]);
    }
}
