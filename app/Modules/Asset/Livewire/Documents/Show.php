<?php

declare(strict_types=1);

namespace App\Modules\Asset\Livewire\Documents;

use App\Core\Support\HouseholdContext;
use App\Modules\Asset\Actions\RenewDocument;
use App\Modules\Asset\Models\AssetDocument;
use App\Modules\Finance\Contracts\FinanceGateway;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Show extends Component
{
    public string $documentId;

    // Renew Modal State
    public bool $showRenewModal = false;
    public string $renewExpiresOn = '';
    public ?int $renewCostPaid = null;
    public ?int $newRenewalCostEstimate = null;
    public bool $renewAddToFinance = false;
    public ?string $renewAccountId = null;

    public function mount(string $document, FinanceGateway $financeGateway): void
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        $doc = AssetDocument::where('household_id', $household?->id)->findOrFail($document);
        $this->documentId = $doc->id;

        $base = $doc->expires_on ?? CarbonImmutable::today();
        $this->renewExpiresOn = $base->addYearNoOverflow()->toDateString();
        $this->renewCostPaid = $doc->renewal_cost_estimate;
        $this->newRenewalCostEstimate = $doc->renewal_cost_estimate;
        $this->renewAddToFinance = $doc->add_to_finance_plan;

        $accountOpts = $financeGateway->accountOptions();
        if (count($accountOpts) > 0) {
            $this->renewAccountId = array_key_first($accountOpts);
        }
    }

    public function openRenewModal(): void
    {
        $this->showRenewModal = true;
    }

    public function saveRenewal(RenewDocument $renewDocument): mixed
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        $doc = AssetDocument::where('household_id', $household?->id)->findOrFail($this->documentId);

        $this->validate([
            'renewExpiresOn' => 'required|date',
            'renewCostPaid' => 'nullable|integer|min:0',
            'newRenewalCostEstimate' => 'nullable|integer|min:0',
        ]);

        $newDoc = $renewDocument->handle(
            oldDoc: $doc,
            data: [
                'expires_on' => $this->renewExpiresOn,
                'cost_paid' => $this->renewCostPaid,
                'renewal_cost_estimate' => $this->newRenewalCostEstimate,
                'add_to_finance_plan' => $this->renewAddToFinance,
                'account_id' => $this->renewAccountId,
            ],
            actor: auth()->user(),
        );

        $this->showRenewModal = false;

        return redirect()->route('assets.documents.show', $newDoc->id);
    }

    public function render(FinanceGateway $financeGateway): View
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        $doc = AssetDocument::with(['asset.location', 'creator'])
            ->where('household_id', $household?->id)
            ->findOrFail($this->documentId);

        $accountOptions = $financeGateway->accountOptions();

        return view('modules.asset.documents.show', [
            'doc' => $doc,
            'accountOptions' => $accountOptions,
        ])->layout('layouts.app', ['title' => $doc->title]);
    }
}
