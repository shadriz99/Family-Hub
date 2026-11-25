<?php

declare(strict_types=1);

namespace App\Core\Services;

use App\Core\Alerts\AlertAggregator;
use App\Core\Models\Household;
use App\Core\Notifications\DailyDigestNotification;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Asset\Enums\DocumentStatus;
use App\Modules\Asset\Enums\PlanStatus;
use App\Modules\Asset\Models\AssetDocument;
use App\Modules\Asset\Models\MaintenancePlan;
use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Models\PlannedItem;
use App\Modules\Organizer\Enums\TaskStatus;
use App\Modules\Organizer\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;

final class DailyDigestService
{
    public function __construct(
        private readonly HouseholdContext $context,
        private readonly AlertAggregator $alertAggregator,
        private readonly QuietHoursCalculator $quietHoursCalculator,
    ) {
    }

    /**
     * Attempts to send a daily digest to a user within their household context.
     * Returns true if sent, false if skipped/suppressed/already sent.
     */
    public function sendToUser(Household $household, User $user, ?CarbonImmutable $now = null, bool $force = false): bool
    {
        $timezone = $household->timezone ?: 'Asia/Jakarta';
        $now = $now ?? CarbonImmutable::now('UTC');
        $localNow = $now->setTimezone($timezone);
        $todayDateString = $localNow->toDateString();

        // 1. Idempotency check: Already sent for today in household timezone? (ignored if forced)
        if (! $force && $user->last_daily_digest_sent_on?->toDateString() === $todayDateString) {
            return false;
        }

        // 2. Quiet hours check: Is user currently in quiet hours? (ignored if forced)
        if (! $force && $this->isInQuietHours($user, $now, $timezone)) {
            return false;
        }

        // 3. Build digest content under household tenant context
        return $this->context->runInContext($household, function () use ($household, $user, $localNow, $todayDateString): bool {
            $digestData = $this->gatherDigestData($household, $localNow);

            // 4. Suppression rule: If zero due bills, zero tasks, zero asset dues, and zero alerts -> suppress!
            if (empty($digestData['due_bills'])
                && empty($digestData['tasks'])
                && empty($digestData['asset_dues'])
                && empty($digestData['alerts'])) {
                return false;
            }

            // 5. Dispatch notification (database, push, mail)
            $user->notify(new DailyDigestNotification($household, $digestData));

            // 6. Record last sent date
            $user->last_daily_digest_sent_on = $todayDateString;
            $user->save();

            return true;
        });
    }

    /**
     * Checks if current instant falls into user's configured quiet hours.
     */
    public function isInQuietHours(User $user, CarbonImmutable $now, string $timezone): bool
    {
        if (empty($user->quiet_hours_start) || empty($user->quiet_hours_end)) {
            return false;
        }

        $effectiveTime = $this->quietHoursCalculator->calculateEffectiveTime(
            $now,
            $user->quiet_hours_start,
            $user->quiet_hours_end,
            $timezone
        );

        return $effectiveTime->greaterThan($now);
    }

    /**
     * @return array{
     *     date: string,
     *     due_bills: array<int, array{name: string, amount: int, due_date: string, is_overdue: bool}>,
     *     tasks: array<int, array{title: string, priority: string, due_date: string, is_overdue: bool}>,
     *     asset_dues: array<int, array{title: string, type: string, due_date: string, is_overdue: bool}>,
     *     alerts: array<int, array{title: string, message: string, level: string}>
     * }
     */
    public function gatherDigestData(Household $household, CarbonImmutable $localNow): array
    {
        $today = $localNow->startOfDay();
        $dueBills = [];
        $tasks = [];
        $assetDues = [];

        // A. Finance Planned Items (if table exists)
        if (Schema::hasTable('fin_planned_items')) {
            $plannedItems = PlannedItem::query()
                ->where('household_id', $household->id)
                ->whereIn('status', [PlannedStatus::Planned, PlannedStatus::Partial])
                ->whereDate('due_date', '<=', $today->toDateString())
                ->orderBy('due_date')
                ->take(10)
                ->get();

            foreach ($plannedItems as $item) {
                $itemDue = CarbonImmutable::parse($item->due_date);
                $dueBills[] = [
                    'name' => $item->name,
                    'amount' => (int) $item->amount,
                    'due_date' => $itemDue->toDateString(),
                    'is_overdue' => $itemDue->lessThan($today),
                ];
            }
        }

        // B. Organizer Tasks (if table exists)
        if (Schema::hasTable('org_tasks')) {
            $orgTasks = Task::query()
                ->where('household_id', $household->id)
                ->where('status', TaskStatus::Open)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<=', $today->toDateString())
                ->orderBy('due_date')
                ->take(10)
                ->get();

            foreach ($orgTasks as $t) {
                $taskDue = CarbonImmutable::parse($t->due_date);
                $tasks[] = [
                    'title' => $t->title,
                    'priority' => $t->priority->value ?? 'normal',
                    'due_date' => $taskDue->toDateString(),
                    'is_overdue' => $taskDue->lessThan($today),
                ];
            }
        }

        // C. Asset Maintenance Plans & Documents (if tables exist)
        if (Schema::hasTable('ast_maintenance_plans')) {
            $plans = MaintenancePlan::query()
                ->where('household_id', $household->id)
                ->where('is_active', true)
                ->whereNotNull('next_due_on')
                ->whereDate('next_due_on', '<=', $today->toDateString())
                ->orderBy('next_due_on')
                ->take(5)
                ->get();

            foreach ($plans as $p) {
                $planDue = CarbonImmutable::parse($p->next_due_on);
                $assetDues[] = [
                    'title' => $p->name,
                    'type' => __('Perawatan Aset'),
                    'due_date' => $planDue->toDateString(),
                    'is_overdue' => $planDue->lessThan($today),
                ];
            }
        }

        if (Schema::hasTable('ast_documents')) {
            $docs = AssetDocument::query()
                ->where('household_id', $household->id)
                ->where('status', DocumentStatus::Active)
                ->whereNotNull('expires_on')
                ->whereDate('expires_on', '<=', $today->toDateString())
                ->orderBy('expires_on')
                ->take(5)
                ->get();

            foreach ($docs as $d) {
                $docDue = CarbonImmutable::parse($d->expires_on);
                $assetDues[] = [
                    'title' => $d->title,
                    'type' => __('Perpanjangan Dokumen'),
                    'due_date' => $docDue->toDateString(),
                    'is_overdue' => $docDue->lessThan($today),
                ];
            }
        }

        // D. Active alerts (top 3)
        $rawAlerts = $this->alertAggregator->for($household, 3);
        $alerts = [];
        foreach ($rawAlerts as $a) {
            $alerts[] = [
                'title' => $a->title,
                'message' => $a->body,
                'level' => $a->level,
            ];
        }

        return [
            'date' => $localNow->translatedFormat('d F Y'),
            'total_items' => count($dueBills) + count($tasks) + count($assetDues) + count($alerts),
            'due_bills' => $dueBills,
            'tasks' => $tasks,
            'asset_dues' => $assetDues,
            'alerts' => $alerts,
        ];
    }
}
