<?php

declare(strict_types=1);

namespace App\Core\Services;

use App\Core\Enums\ReminderDeliveryStatus;
use App\Core\Enums\ReminderStatus;
use App\Core\Models\Household;
use App\Core\Models\Reminder;
use App\Core\Models\ReminderDelivery;
use App\Core\Notifications\ReminderNotification;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ReminderService
{
    /** Q-M9: Staged backoff intervals in seconds: 5 mins, 15 mins, 30 mins. */
    public const RETRY_BACKOFF_SECONDS = [300, 900, 1800];

    public const MAX_ATTEMPTS = 3;

    public function __construct(
        private readonly QuietHoursCalculator $quietHoursCalculator,
        private readonly HouseholdContext $householdContext,
    ) {
    }

    /**
     * Schedules or updates a logical reminder and its per-recipient deliveries.
     *
     * @param array{
     *     title: string,
     *     body?: string|null,
     *     url: string,
     *     remind_at: DateTimeInterface,
     *     recipient_user_id?: string|null,
     * } $payload
     */
    public function schedule(
        Household $household,
        Model $remindable,
        string $key,
        array $payload,
        ?User $actor = null
    ): Reminder {
        return $this->householdContext->runInContext($household, function () use ($household, $remindable, $key, $payload, $actor): Reminder {
            return DB::transaction(function () use ($household, $remindable, $key, $payload, $actor): Reminder {
                $remindAtUtc = CarbonImmutable::instance($payload['remind_at'])->setTimezone('UTC');
                $recipientUserId = $payload['recipient_user_id'] ?? null;

                $reminder = Reminder::query()
                    ->where('remindable_type', $remindable->getMorphClass())
                    ->where('remindable_id', (string) $remindable->getKey())
                    ->where('key', $key)
                    ->first();

                if ($reminder === null) {
                    $reminder = new Reminder();
                    $reminder->household_id = $household->getKey();
                    $reminder->remindable_type = $remindable->getMorphClass();
                    $reminder->remindable_id = (string) $remindable->getKey();
                    $reminder->key = $key;
                }

                $reminder->title = trim($payload['title']);
                $reminder->body = isset($payload['body']) && trim((string) $payload['body']) !== '' ? trim((string) $payload['body']) : null;
                $reminder->url = trim($payload['url']);
                $reminder->remind_at = $remindAtUtc;
                $reminder->recipient_user_id = $recipientUserId;
                $reminder->status = ReminderStatus::Scheduled;
                if ($actor !== null) {
                    $reminder->created_by = $actor->getKey();
                }
                $reminder->save();

                // Determine recipients
                if ($recipientUserId !== null) {
                    $recipients = User::query()->whereKey($recipientUserId)->get();
                } else {
                    $memberUserIds = $household->members()->pluck('user_id');
                    $recipients = User::query()->whereIn('id', $memberUserIds)->get();
                }

                $recipientIds = $recipients->pluck('id')->all();

                // Cancel pending deliveries for users no longer in the audience
                $reminder->deliveries()
                    ->where('status', ReminderDeliveryStatus::Pending)
                    ->whereNotIn('recipient_user_id', $recipientIds)
                    ->get()
                    ->each(fn (ReminderDelivery $d) => $d->markCancelled());

                // Create or update deliveries for active recipients
                foreach ($recipients as $recipient) {
                    $effectiveRemindAt = $this->quietHoursCalculator->calculateEffectiveTime(
                        $remindAtUtc,
                        $recipient->quiet_hours_start,
                        $recipient->quiet_hours_end,
                        $household->timezone,
                    );

                    $delivery = ReminderDelivery::query()
                        ->where('reminder_id', $reminder->getKey())
                        ->where('recipient_user_id', $recipient->getKey())
                        ->first();

                    if ($delivery === null) {
                        $delivery = new ReminderDelivery();
                        $delivery->household_id = $household->getKey();
                        $delivery->reminder_id = $reminder->getKey();
                        $delivery->recipient_user_id = $recipient->getKey();
                        $delivery->created_by = $actor?->getKey();
                        $delivery->status = ReminderDeliveryStatus::Pending;
                        $delivery->remind_at = $effectiveRemindAt;
                        $delivery->save();
                    } elseif ($delivery->isPending() || $delivery->isCancelled()) {
                        $delivery->status = ReminderDeliveryStatus::Pending;
                        $delivery->remind_at = $effectiveRemindAt;
                        $delivery->next_attempt_at = null;
                        $delivery->save();
                    }
                    // If already sent or failed, leave as-is per idempotency specifications
                }

                return $reminder;
            });
        });
    }

    /**
     * Cancels a logical reminder and all its unsent deliveries.
     */
    public function cancel(Reminder $reminder): void
    {
        DB::transaction(function () use ($reminder): void {
            $reminder->status = ReminderStatus::Cancelled;
            $reminder->save();

            $reminder->deliveries()
                ->where('status', ReminderDeliveryStatus::Pending)
                ->get()
                ->each(fn (ReminderDelivery $delivery) => $delivery->markCancelled());
        });
    }

    /**
     * Synchronizes future household-level reminders for a newly joined member.
     *
     * @return int Number of deliveries created
     */
    public function syncDeliveriesForNewMember(Household $household, User $newMember): int
    {
        return $this->householdContext->runInContext($household, function () use ($household, $newMember): int {
            return DB::transaction(function () use ($household, $newMember): int {
                $nowUtc = CarbonImmutable::now('UTC');

                // Find all scheduled reminders targeting the entire household in the future
                $futureReminders = Reminder::query()
                    ->where('household_id', $household->getKey())
                    ->where('status', ReminderStatus::Scheduled)
                    ->whereNull('recipient_user_id')
                    ->where('remind_at', '>', $nowUtc)
                    ->get();

                $createdCount = 0;

                foreach ($futureReminders as $reminder) {
                    $exists = ReminderDelivery::query()
                        ->where('reminder_id', $reminder->getKey())
                        ->where('recipient_user_id', $newMember->getKey())
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $effectiveRemindAt = $this->quietHoursCalculator->calculateEffectiveTime(
                        $reminder->remind_at,
                        $newMember->quiet_hours_start,
                        $newMember->quiet_hours_end,
                        $household->timezone,
                    );

                    $delivery = new ReminderDelivery();
                    $delivery->household_id = $household->getKey();
                    $delivery->reminder_id = $reminder->getKey();
                    $delivery->recipient_user_id = $newMember->getKey();
                    $delivery->status = ReminderDeliveryStatus::Pending;
                    $delivery->remind_at = $effectiveRemindAt;
                    $delivery->save();

                    $createdCount++;
                }

                return $createdCount;
            });
        });
    }

    /**
     * Dispatches pending reminder deliveries that are due.
     *
     * @param callable(ReminderDelivery): bool|null $sender Optional sender callback
     * @return array{dispatched: int, failed: int, retried: int}
     */
    public function dispatchDueDeliveries(?CarbonImmutable $now = null, ?callable $sender = null): array
    {
        $nowUtc = $now ?? CarbonImmutable::now('UTC');

        $dueDeliveries = ReminderDelivery::withoutGlobalScopes()
            ->with(['household', 'recipient'])
            ->where('status', ReminderDeliveryStatus::Pending)
            ->where('remind_at', '<=', $nowUtc)
            ->where(function ($query) use ($nowUtc) {
                $query->whereNull('next_attempt_at')
                    ->orWhere('next_attempt_at', '<=', $nowUtc);
            })
            ->get();

        $stats = [
            'dispatched' => 0,
            'failed' => 0,
            'retried' => 0,
        ];

        foreach ($dueDeliveries as $delivery) {
            $household = $delivery->household;

            if ($household === null) {
                continue;
            }

            $this->householdContext->runInContext($household, function () use ($delivery, $nowUtc, $sender, &$stats): void {
                $delivery->loadMissing(['reminder', 'recipient.pushSubscriptions']);

                try {
                    $success = true;

                    if ($sender !== null) {
                        $success = (bool) $sender($delivery);
                    } else {
                        $recipient = $delivery->recipient;
                        if ($recipient !== null) {
                            $recipient->notify(new ReminderNotification($delivery));
                        }
                    }

                    if ($success) {
                        $delivery->markSent($nowUtc);
                        $stats['dispatched']++;
                    } else {
                        $this->handleDeliveryFailure($delivery, 'SENDER_RETURNED_FALSE', $nowUtc, $stats);
                    }
                } catch (Throwable $e) {
                    $errorCode = mb_substr(class_basename($e) . ':' . $e->getMessage(), 0, 50);
                    $this->handleDeliveryFailure($delivery, $errorCode, $nowUtc, $stats);
                }
            });
        }

        return $stats;
    }

    /**
     * Handles retry backoff and terminal failure according to Q-M9.
     *
     * @param array{dispatched: int, failed: int, retried: int} $stats
     */
    private function handleDeliveryFailure(
        ReminderDelivery $delivery,
        string $errorCode,
        CarbonImmutable $nowUtc,
        array &$stats
    ): void {
        $nextAttemptIndex = $delivery->attempt_count; // 0 for 1st fail, 1 for 2nd fail, 2 for 3rd fail
        $isTerminal = ($nextAttemptIndex + 1) >= self::MAX_ATTEMPTS;

        if ($isTerminal) {
            $delivery->recordFailure($errorCode, null, true);
            $stats['failed']++;
        } else {
            $backoffSeconds = self::RETRY_BACKOFF_SECONDS[$nextAttemptIndex] ?? 1800;
            $nextAttemptAt = $nowUtc->addSeconds($backoffSeconds);
            $delivery->recordFailure($errorCode, $nextAttemptAt, false);
            $stats['retried']++;
        }
    }
}
