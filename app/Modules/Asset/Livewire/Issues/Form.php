<?php

declare(strict_types=1);

namespace App\Modules\Asset\Livewire\Issues;

use App\Core\Support\HouseholdContext;
use App\Modules\Asset\Enums\IssuePriority;
use App\Modules\Asset\Enums\IssueStatus;
use App\Modules\Asset\Models\Asset;
use App\Modules\Asset\Models\Issue;
use App\Modules\Asset\Models\Location;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Form extends Component
{
    public ?string $issueId = null;

    public string $title = '';
    public ?string $asset_id = null;
    public ?string $location_id = null;
    public string $priority = 'medium';
    public string $description = '';

    public function mount(?string $asset = null, ?string $issue = null): void
    {
        $household = app(HouseholdContext::class)->currentOrNull();

        if ($issue) {
            $existing = Issue::where('household_id', $household?->id)->where('id', $issue)->firstOrFail();
            $this->issueId = $existing->id;
            $this->title = $existing->title;
            $this->asset_id = $existing->asset_id;
            $this->location_id = $existing->location_id;
            $this->priority = $existing->priority->value;
            $this->description = $existing->description ?? '';
        } elseif ($asset) {
            $this->asset_id = $asset;
            $a = Asset::find($asset);
            if ($a) {
                $this->location_id = $a->location_id;
            }
        }
    }

    public function updatedAssetId(?string $val): void
    {
        if ($val) {
            $a = Asset::find($val);
            if ($a && $a->location_id && ! $this->location_id) {
                $this->location_id = $a->location_id;
            }
        }
    }

    public function save(): mixed
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        if (! $household) {
            return null;
        }

        $this->validate([
            'title' => 'required|string|max:150',
            'priority' => 'required|in:low,medium,urgent',
            'asset_id' => 'nullable|exists:ast_assets,id',
            'location_id' => 'nullable|exists:ast_locations,id',
            'description' => 'nullable|string',
        ]);

        $user = auth()->user();
        $prio = IssuePriority::from($this->priority);

        if ($this->issueId) {
            $issue = Issue::where('household_id', $household->id)->findOrFail($this->issueId);
            $issue->update([
                'title' => $this->title,
                'asset_id' => $this->asset_id ?: null,
                'location_id' => $this->location_id ?: null,
                'priority' => $prio,
                'description' => $this->description ?: null,
            ]);
        } else {
            $issue = Issue::create([
                'household_id' => $household->id,
                'title' => $this->title,
                'asset_id' => $this->asset_id ?: null,
                'location_id' => $this->location_id ?: null,
                'priority' => $prio,
                'status' => IssueStatus::Open,
                'description' => $this->description ?: null,
                'reported_by' => (string) auth()->id(),
            ]);
        }

        return redirect()->route('assets.issues.show', $issue->id);
    }

    public function render(): View
    {
        $household = app(HouseholdContext::class)->currentOrNull();

        $assets = Asset::where('household_id', $household?->id)->orderBy('name')->get();
        $locations = Location::where('household_id', $household?->id)->orderBy('sort_order')->get();

        return view('modules.asset.issues.form', [
            'assets' => $assets,
            'locations' => $locations,
        ]);
    }
}
