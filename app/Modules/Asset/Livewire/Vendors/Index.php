<?php

declare(strict_types=1);

namespace App\Modules\Asset\Livewire\Vendors;

use App\Core\Support\HouseholdContext;
use App\Modules\Asset\Models\Vendor;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Index extends Component
{
    public string $search = '';

    public function render(): View
    {
        $household = app(HouseholdContext::class)->currentOrNull();

        $query = Vendor::query()
            ->withCount('serviceRecords')
            ->where('household_id', $household?->id);

        if ($this->search !== '') {
            $s = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)
                    ->orWhere('phone', 'like', $s)
                    ->orWhere('whatsapp', 'like', $s);
            });
        }

        $vendors = $query->orderByDesc('is_favorite')->orderBy('name')->get();

        return view('modules.asset.vendors.index', [
            'vendors' => $vendors,
            'totalCount' => $vendors->count(),
        ])->layout('layouts.app', ['title' => 'Daftar Vendor & Bengkel']);
    }
}
