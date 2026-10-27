<?php

declare(strict_types=1);

namespace App\Modules\Asset\Livewire\Documents;

use App\Core\Support\HouseholdContext;
use App\Modules\Asset\Enums\DocumentStatus;
use App\Modules\Asset\Enums\DocumentType;
use App\Modules\Asset\Models\Asset;
use App\Modules\Asset\Models\AssetDocument;
use App\Modules\Finance\Contracts\FinanceGateway;
use App\Modules\Finance\DTO\PlannedExpenseData;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Form extends Component
{
    public ?string $documentId = null;
    public ?string $assetId = null;

    public string $type = 'warranty';
    public string $title = '';
    public string $number = '';
    public string $issuer = '';
    public ?string $issued_on = null;
    public ?string $expires_on = null;
    public ?int $renewal_cost_estimate = null;
    public bool $add_to_finance_plan = false;
    public string $notes = '';

    public function mount(?string $asset = null, ?string $document = null): void
    {
        $household = app(HouseholdContext::class)->currentOrNull();

        if ($document) {
            $existing = AssetDocument::where('household_id', $household?->id)->where('id', $document)->firstOrFail();
            $this->documentId = $existing->id;
            $this->assetId = $existing->asset_id;
            $this->type = $existing->type->value;
            $this->title = $existing->title;
            $this->number = $existing->number ?? '';
            $this->issuer = $existing->issuer ?? '';
            $this->issued_on = $existing->issued_on?->toDateString();
            $this->expires_on = $existing->expires_on?->toDateString();
            $this->renewal_cost_estimate = $existing->renewal_cost_estimate;
            $this->add_to_finance_plan = $existing->add_to_finance_plan;
            $this->notes = $existing->notes ?? '';
        } elseif ($asset) {
            $this->assetId = $asset;
            $a = Asset::find($asset);
            if ($a) {
                $this->title = 'Dokumen ' . $a->name;
            }
        }
    }

    public function save(FinanceGateway $financeGateway): mixed
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        if (! $household) {
            return null;
        }

        $this->validate([
            'title' => 'required|string|max:100',
            'type' => 'required|in:warranty,vehicle_tax_annual,vehicle_registration_5y,insurance,inspection,certificate,other',
            'assetId' => 'nullable|exists:ast_assets,id',
            'number' => 'nullable|string|max:80',
            'issuer' => 'nullable|string|max:100',
            'issued_on' => 'nullable|date',
            'expires_on' => 'nullable|date',
            'renewal_cost_estimate' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $user = auth()->user();
        $docType = DocumentType::from($this->type);
        $issuedOn = $this->issued_on ? CarbonImmutable::parse($this->issued_on) : null;
        $expiresOn = $this->expires_on ? CarbonImmutable::parse($this->expires_on) : null;

        if ($this->documentId) {
            $doc = AssetDocument::where('household_id', $household->id)->findOrFail($this->documentId);
            $doc->update([
                'asset_id' => $this->assetId ?: null,
                'type' => $docType,
                'title' => $this->title,
                'number' => $this->number ?: null,
                'issuer' => $this->issuer ?: null,
                'issued_on' => $issuedOn,
                'expires_on' => $expiresOn,
                'renewal_cost_estimate' => $this->renewal_cost_estimate,
                'add_to_finance_plan' => $this->add_to_finance_plan,
                'notes' => $this->notes ?: null,
            ]);
        } else {
            $doc = AssetDocument::create([
                'household_id' => $household->id,
                'asset_id' => $this->assetId ?: null,
                'type' => $docType,
                'title' => $this->title,
                'number' => $this->number ?: null,
                'issuer' => $this->issuer ?: null,
                'issued_on' => $issuedOn,
                'expires_on' => $expiresOn,
                'remind_days' => [30, 7, 0],
                'status' => DocumentStatus::Active,
                'renewal_cost_estimate' => $this->renewal_cost_estimate,
                'add_to_finance_plan' => $this->add_to_finance_plan,
                'notes' => $this->notes ?: null,
                'created_by' => $user?->id,
            ]);
        }

        // BR-AST-031: Planned expense synchronization
        if ($this->add_to_finance_plan && $this->renewal_cost_estimate && $expiresOn) {
            $catId = $financeGateway->defaultCategoryId('asset_maintenance') ?? '';
            if ($catId !== '') {
                $financeGateway->upsertPlannedExpense(new PlannedExpenseData(
                    name: "Perpanjang: {$doc->title}",
                    categoryId: $catId,
                    accountId: null,
                    amount: (int) $this->renewal_cost_estimate,
                    dueDate: $expiresOn,
                    sourceType: 'asset_document',
                    sourceId: $doc->id,
                ));
            }
        } elseif (! $this->add_to_finance_plan && $this->documentId) {
            try {
                $financeGateway->cancelPlannedExpenses('asset_document', $doc->id);
            } catch (\Throwable) {
            }
        }

        if ($doc->asset_id) {
            return redirect()->route('assets.show', ['asset' => $doc->asset_id, 'tab' => 'documents']);
        }

        return redirect()->route('assets.documents');
    }

    public function render(): View
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        $assets = Asset::where('household_id', $household?->id)->orderBy('name')->get();

        return view('modules.asset.documents.form', [
            'assets' => $assets,
        ])->layout('layouts.app', ['title' => $this->documentId ? 'Edit Dokumen' : 'Tambah Dokumen']);
    }
}
