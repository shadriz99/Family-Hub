<?php

declare(strict_types=1);

namespace App\Modules\Asset\Livewire\Documents;

use App\Core\Support\HouseholdContext;
use App\Modules\Asset\Enums\DocumentStatus;
use App\Modules\Asset\Models\AssetDocument;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
final class Index extends Component
{
    #[Url]
    public bool $expiringOnly = false;

    #[Url]
    public string $type = '';

    public function render(): View
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        $today = CarbonImmutable::today();
        $in60Days = $today->addDays(60);

        $query = AssetDocument::query()
            ->with('asset.location')
            ->where('household_id', $household?->id);

        if ($this->expiringOnly) {
            $query->where('status', DocumentStatus::Active)
                ->whereNotNull('expires_on')
                ->whereBetween('expires_on', [$today->toDateString(), $in60Days->toDateString()]);
        }

        if ($this->type !== '') {
            $query->where('type', $this->type);
        }

        $documents = $query->orderBy('expires_on')->get();

        return view('modules.asset.documents.index', [
            'documents' => $documents,
            'totalCount' => $documents->count(),
        ])->layout('layouts.app', ['title' => 'Dokumen Aset']);
    }
}
