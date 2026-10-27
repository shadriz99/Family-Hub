<?php

declare(strict_types=1);

namespace App\Modules\Asset\Integrations;

use App\Core\Dashboard\DashboardWidget;
use App\Core\Models\Household;
use App\Core\Support\HouseholdContext;
use App\Modules\Asset\Enums\DocumentStatus;
use App\Modules\Asset\Enums\IssueStatus;
use App\Modules\Asset\Enums\PlanStatus;
use App\Modules\Asset\Models\AssetDocument;
use App\Modules\Asset\Models\Issue;
use App\Modules\Asset\Models\MaintenancePlan;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Throwable;

final class AssetAttentionWidget implements DashboardWidget
{
    public function key(): string
    {
        return 'asset_attention';
    }

    public function module(): string
    {
        return 'asset';
    }

    public function order(): int
    {
        return 30;
    }

    public function isEnabled(Household $household): bool
    {
        return (bool) config('modules.asset', false);
    }

    public function render(Household $household): View|string
    {
        return app(HouseholdContext::class)->runInContext($household, function () use ($household): View|string {
            try {
                $today = CarbonImmutable::today();
                $in30Days = $today->addDays(30);

                $plans = MaintenancePlan::query()
                    ->where('household_id', $household->id)
                    ->where('is_active', true)
                    ->get();

                $overdueOrSoonCount = 0;
                foreach ($plans as $plan) {
                    $status = $plan->computedStatus($today);
                    if (in_array($status, [PlanStatus::Overdue, PlanStatus::Due, PlanStatus::Soon], true)) {
                        $overdueOrSoonCount++;
                    }
                }

                $expiringDocsCount = AssetDocument::query()
                    ->where('household_id', $household->id)
                    ->where('status', DocumentStatus::Active)
                    ->whereNotNull('expires_on')
                    ->whereBetween('expires_on', [$today->toDateString(), $in30Days->toDateString()])
                    ->count();

                $openIssuesCount = Issue::query()
                    ->where('household_id', $household->id)
                    ->whereIn('status', [IssueStatus::Open, IssueStatus::Scheduled, IssueStatus::InProgress])
                    ->count();

                return view('modules.asset.widgets.attention', [
                    'overdueOrSoonCount' => $overdueOrSoonCount,
                    'expiringDocsCount' => $expiringDocsCount,
                    'openIssuesCount' => $openIssuesCount,
                ]);
            } catch (Throwable) {
                return '';
            }
        });
    }
}
