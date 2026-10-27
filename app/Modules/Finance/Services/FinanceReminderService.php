<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Core\Models\Reminder;
use App\Core\Services\ReminderService;
use App\Modules\Finance\Enums\PlannedStatus;
use App\Modules\Finance\Models\Loan;
use App\Modules\Finance\Models\PlannedItem;
use App\Modules\Finance\Models\RecurringBill;
use Carbon\CarbonImmutable;

final class FinanceReminderService
{
    public const SCHEDULE_WINDOW_DAYS = 35;

    public function __construct(
        private readonly ReminderService $reminderService,
    ) {
    }

    /**
     * Schedules reminders for a PlannedItem if within the 35-day window.
     */
    public function scheduleForItem(PlannedItem $item): void
    {
        $household = $item->household;
        $today = CarbonImmutable::now($household->timezone)->startOfDay();
        $dueDate = $item->due_date->startOfDay();

        // If item is already settled or cancelled, cancel its reminders
        if (in_array($item->status, [PlannedStatus::Paid, PlannedStatus::Skipped, PlannedStatus::Cancelled], true)) {
            $this->cancelForItem($item);

            return;
        }

        // Only schedule if within 35 days window
        if ($dueDate->greaterThan($today->addDays(self::SCHEDULE_WINDOW_DAYS))) {
            return;
        }

        $remindDaysBefore = $this->resolveRemindDaysBefore($item);
        $url = "/finance/plan?period={$item->period}#item-{$item->id}";

        // 1. H-n reminder at 08:00
        if ($remindDaysBefore > 0) {
            $hnDate = $dueDate->subDays($remindDaysBefore)->setTime(8, 0);
            if ($hnDate->greaterThanOrEqualTo($today)) {
                $this->reminderService->schedule(
                    $household,
                    $item,
                    "h-{$remindDaysBefore}",
                    [
                        'title' => "Pengingat: {$item->name}",
                        'body' => "Jatuh tempo dalam {$remindDaysBefore} hari.",
                        'url' => $url,
                        'remind_at' => $hnDate,
                    ]
                );
            }
        }

        // 2. H0 reminder at 08:00
        $h0Date = $dueDate->setTime(8, 0);
        if ($h0Date->greaterThanOrEqualTo($today)) {
            $this->reminderService->schedule(
                $household,
                $item,
                'h0',
                [
                    'title' => "Jatuh Tempo Hari Ini: {$item->name}",
                    'body' => "Pembayaran jatuh tempo hari ini.",
                    'url' => $url,
                    'remind_at' => $h0Date,
                ]
            );
        }

        // 3. Overdue-1 reminder at 08:00
        $overdue1Date = $dueDate->addDay()->setTime(8, 0);
        if ($item->isOverdue() && $overdue1Date->greaterThanOrEqualTo($today)) {
            $this->reminderService->schedule(
                $household,
                $item,
                'overdue-1',
                [
                    'title' => "Terlambat: {$item->name}",
                    'body' => "Kewajiban ini telah melewati tanggal jatuh tempo.",
                    'url' => $url,
                    'remind_at' => $overdue1Date,
                ]
            );
        }
    }

    /**
     * Cancels all active reminders for this PlannedItem.
     */
    public function cancelForItem(PlannedItem $item): void
    {
        $reminders = Reminder::query()
            ->where('remindable_type', $item->getMorphClass())
            ->where('remindable_id', (string) $item->id)
            ->get();

        foreach ($reminders as $reminder) {
            $this->reminderService->cancel($reminder);
        }
    }

    private function resolveRemindDaysBefore(PlannedItem $item): int
    {
        if ($item->source_type->value === 'loan' && $item->source_id !== null) {
            $loan = Loan::query()->find($item->source_id);
            if ($loan !== null) {
                return $loan->remind_days_before;
            }
        }

        if ($item->source_type->value === 'recurring_bill' && $item->source_id !== null) {
            $bill = RecurringBill::query()->find($item->source_id);
            if ($bill !== null) {
                return $bill->remind_days_before;
            }
        }

        return 3;
    }
}
