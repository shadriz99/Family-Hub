<?php

declare(strict_types=1);

namespace App\Modules\Asset\Livewire\Issues;

use App\Core\Support\HouseholdContext;
use App\Modules\Asset\Enums\IssuePriority;
use App\Modules\Asset\Enums\IssueStatus;
use App\Modules\Asset\Models\Issue;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
final class Index extends Component
{
    #[Url]
    public string $tab = 'open'; // open, resolved

    public function render(): View
    {
        $household = app(HouseholdContext::class)->currentOrNull();

        $query = Issue::query()
            ->with(['asset', 'location', 'vendor', 'reporter'])
            ->where('household_id', $household?->id);

        if ($this->tab === 'resolved') {
            $query->whereIn('status', [IssueStatus::Resolved, IssueStatus::Cancelled]);
        } else {
            $query->whereIn('status', [IssueStatus::Open, IssueStatus::Scheduled, IssueStatus::InProgress]);
        }

        // Urut prioritas: urgent -> medium -> low, lalu umur terlama
        $issues = $query->get()->sortBy(function (Issue $i): array {
            $pOrder = match ($i->priority) {
                IssuePriority::Urgent => 1,
                IssuePriority::Medium => 2,
                IssuePriority::Low => 3,
            };

            return [$pOrder, $i->created_at?->timestamp];
        });

        return view('modules.asset.issues.index', [
            'issues' => $issues,
            'totalCount' => $issues->count(),
        ])->layout('layouts.app', ['title' => 'Laporan Kerusakan']);
    }
}
