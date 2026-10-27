<?php

declare(strict_types=1);

namespace App\Modules\Asset\Livewire\Issues;

use App\Core\Support\HouseholdContext;
use App\Modules\Asset\Enums\IssueStatus;
use App\Modules\Asset\Models\Issue;
use App\Modules\Asset\Models\Vendor;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Show extends Component
{
    public string $issueId;

    // Schedule modal
    public bool $showScheduleModal = false;
    public string $scheduledOn = '';
    public ?string $scheduleVendorId = null;

    // Resolve modal
    public bool $showResolveModal = false;
    public string $resolutionNote = '';

    public function mount(string $issue): void
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        $record = Issue::where('household_id', $household?->id)->findOrFail($issue);
        $this->issueId = $record->id;
        $this->scheduledOn = CarbonImmutable::today()->toDateString();
        $this->scheduleVendorId = $record->vendor_id;
    }

    public function openScheduleModal(): void
    {
        $this->scheduledOn = CarbonImmutable::today()->toDateString();
        $this->showScheduleModal = true;
    }

    public function saveSchedule(): void
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        $issue = Issue::where('household_id', $household?->id)->findOrFail($this->issueId);

        $this->validate([
            'scheduledOn' => 'required|date|after_or_equal:today',
        ], [
            'scheduledOn.after_or_equal' => 'Tanggal jadwal teknisi harus hari ini atau yang akan datang.',
        ]);

        $issue->update([
            'status' => IssueStatus::Scheduled,
            'scheduled_on' => CarbonImmutable::parse($this->scheduledOn),
            'vendor_id' => $this->scheduleVendorId ?: null,
        ]);

        $this->showScheduleModal = false;
    }

    public function markInProgress(): void
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        $issue = Issue::where('household_id', $household?->id)->findOrFail($this->issueId);

        $issue->update([
            'status' => IssueStatus::InProgress,
        ]);
    }

    public function openResolveModal(): void
    {
        $this->resolutionNote = 'Diperbaiki sendiri / tanpa biaya servis';
        $this->showResolveModal = true;
    }

    public function saveResolve(): void
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        $issue = Issue::where('household_id', $household?->id)->findOrFail($this->issueId);

        $issue->update([
            'status' => IssueStatus::Resolved,
            'resolved_on' => CarbonImmutable::today(),
            'resolution_note' => $this->resolutionNote ?: 'Selesai',
        ]);

        $this->showResolveModal = false;
    }

    public function markCancelled(): void
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        $issue = Issue::where('household_id', $household?->id)->findOrFail($this->issueId);

        $issue->update([
            'status' => IssueStatus::Cancelled,
        ]);
    }

    public function render(): View
    {
        $household = app(HouseholdContext::class)->currentOrNull();
        $issue = Issue::with(['asset.location', 'location', 'vendor', 'reporter', 'serviceRecords'])
            ->where('household_id', $household?->id)
            ->findOrFail($this->issueId);

        $vendors = Vendor::where('household_id', $household?->id)->orderBy('name')->get();

        return view('modules.asset.issues.show', [
            'issue' => $issue,
            'vendors' => $vendors,
        ])->layout('layouts.app', ['title' => $issue->title]);
    }
}
