<?php

declare(strict_types=1);

namespace App\Modules\Asset\Integrations;

use App\Core\Alerts\Alert;
use App\Core\Alerts\AlertSource;
use App\Core\Models\Household;
use App\Core\Support\HouseholdContext;
use App\Modules\Asset\Enums\DocumentStatus;
use App\Modules\Asset\Enums\DocumentType;
use App\Modules\Asset\Enums\IssuePriority;
use App\Modules\Asset\Enums\IssueStatus;
use App\Modules\Asset\Enums\PlanStatus;
use App\Modules\Asset\Models\AssetDocument;
use App\Modules\Asset\Models\Issue;
use App\Modules\Asset\Models\MaintenancePlan;
use Carbon\CarbonImmutable;
use Throwable;

final class AssetAlertSource implements AlertSource
{
    public function key(): string
    {
        return 'asset_alerts';
    }

    public function alerts(Household $h): iterable
    {
        if (! config('modules.asset', false)) {
            return [];
        }

        return app(HouseholdContext::class)->runInContext($h, function () use ($h): iterable {
            try {
                $alerts = [];
                $today = CarbonImmutable::today();
                $in30Days = $today->addDays(30);

                // 1. Maintenance Plans
                $plans = MaintenancePlan::query()
                    ->with('asset')
                    ->where('household_id', $h->id)
                    ->where('is_active', true)
                    ->get();

                $overduePlans = [];
                $dueOrSoonPlans = [];

                foreach ($plans as $plan) {
                    $status = $plan->computedStatus($today);
                    if ($status === PlanStatus::Overdue) {
                        $overduePlans[] = $plan;
                    } elseif ($status === PlanStatus::Due || $status === PlanStatus::Soon) {
                        $dueOrSoonPlans[] = $plan;
                    }
                }

                if (count($overduePlans) > 1) {
                    $count = count($overduePlans);
                    $alerts[] = new Alert(
                        level: 'danger',
                        title: "Ada {$count} jadwal perawatan aset yang terlambat",
                        url: '/assets/maintenance',
                        sourceKey: $this->key(),
                    );
                } elseif (count($overduePlans) === 1) {
                    $p = $overduePlans[0];
                    $assetName = $p->asset->name ?? 'Aset';
                    $alerts[] = new Alert(
                        level: 'danger',
                        title: "Perawatan {$p->name} ({$assetName}) terlambat",
                        url: '/assets/maintenance',
                        sourceKey: $this->key(),
                    );
                }

                foreach ($dueOrSoonPlans as $p) {
                    $assetName = $p->asset->name ?? 'Aset';
                    $alerts[] = new Alert(
                        level: 'warning',
                        title: "Perawatan {$p->name} ({$assetName}) perlu segera dilakukan",
                        url: '/assets/maintenance',
                        sourceKey: $this->key(),
                    );
                }

                // 2. Urgent Open Issues
                $urgentIssuesCount = Issue::query()
                    ->where('household_id', $h->id)
                    ->where('priority', IssuePriority::Urgent)
                    ->whereIn('status', [IssueStatus::Open, IssueStatus::Scheduled, IssueStatus::InProgress])
                    ->count();

                if ($urgentIssuesCount > 0) {
                    $alerts[] = new Alert(
                        level: 'danger',
                        title: "Ada {$urgentIssuesCount} laporan kerusakan mendesak yang terbuka",
                        url: '/assets/issues',
                        sourceKey: $this->key(),
                    );
                }

                // 3. Documents
                $docs = AssetDocument::query()
                    ->with('asset')
                    ->where('household_id', $h->id)
                    ->where('status', DocumentStatus::Active)
                    ->whereNotNull('expires_on')
                    ->get();

                foreach ($docs as $doc) {
                    if (! $doc->expires_on) {
                        continue;
                    }

                    $isWarranty = $doc->type === DocumentType::Warranty;

                    if ($doc->expires_on->isBefore($today)) {
                        if (! $isWarranty) {
                            $alerts[] = new Alert(
                                level: 'danger',
                                title: "Dokumen {$doc->title} sudah kedaluwarsa sejak " . $doc->expires_on->translatedFormat('d M Y'),
                                url: "/assets/documents/{$doc->id}",
                                sourceKey: $this->key(),
                            );
                        }
                    } elseif ($doc->expires_on->between($today, $in30Days)) {
                        $daysLeft = (int) $today->diffInDays($doc->expires_on, false);
                        if ($isWarranty) {
                            $assetName = $doc->asset->name ?? $doc->title;
                            $alerts[] = new Alert(
                                level: 'info',
                                title: "Garansi {$assetName} berakhir {$doc->expires_on->translatedFormat('d M Y')} — cek kondisi sebelum habis",
                                url: "/assets/documents/{$doc->id}",
                                sourceKey: $this->key(),
                            );
                        } else {
                            $alerts[] = new Alert(
                                level: 'warning',
                                title: "Dokumen {$doc->title} berakhir dalam {$daysLeft} hari ({$doc->expires_on->translatedFormat('d M Y')})",
                                url: "/assets/documents/{$doc->id}",
                                sourceKey: $this->key(),
                            );
                        }
                    }
                }

                return $alerts;
            } catch (Throwable) {
                return [];
            }
        });
    }
}
