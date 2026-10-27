<?php

declare(strict_types=1);

namespace App\Core\Models;

use App\Core\Enums\ReminderDeliveryStatus;
use App\Core\Support\BelongsToHousehold;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Concrete delivery of a logical reminder for a specific recipient.
 *
 * @property string $id
 * @property string $household_id
 * @property string $reminder_id
 * @property string $recipient_user_id
 * @property CarbonImmutable $remind_at
 * @property ReminderDeliveryStatus $status
 * @property int $attempt_count
 * @property CarbonImmutable|null $next_attempt_at
 * @property CarbonImmutable|null $sent_at
 * @property string|null $last_error_code
 * @property string|null $created_by
 */
class ReminderDelivery extends Model
{
    use BelongsToHousehold;
    use HasUlids;

    protected $fillable = [
        'household_id',
        'reminder_id',
        'recipient_user_id',
        'remind_at',
        'status',
        'attempt_count',
        'next_attempt_at',
        'sent_at',
        'last_error_code',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReminderDeliveryStatus::class,
            'remind_at' => 'immutable_datetime',
            'next_attempt_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
            'attempt_count' => 'integer',
        ];
    }

    /** @return BelongsTo<Reminder, $this> */
    public function reminder(): BelongsTo
    {
        return $this->belongsTo(Reminder::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === ReminderDeliveryStatus::Pending;
    }

    public function isSent(): bool
    {
        return $this->status === ReminderDeliveryStatus::Sent;
    }

    public function isCancelled(): bool
    {
        return $this->status === ReminderDeliveryStatus::Cancelled;
    }

    public function isFailed(): bool
    {
        return $this->status === ReminderDeliveryStatus::Failed;
    }

    public function markSent(CarbonImmutable $at): void
    {
        $this->status = ReminderDeliveryStatus::Sent;
        $this->sent_at = $at;
        $this->next_attempt_at = null;
        $this->save();
    }

    public function markCancelled(): void
    {
        $this->status = ReminderDeliveryStatus::Cancelled;
        $this->next_attempt_at = null;
        $this->save();
    }

    public function recordFailure(string $errorCode, ?CarbonImmutable $nextAttemptAt, bool $terminal): void
    {
        $this->attempt_count++;
        $this->last_error_code = mb_substr($errorCode, 0, 50);

        if ($terminal) {
            $this->status = ReminderDeliveryStatus::Failed;
            $this->next_attempt_at = null;
        } else {
            $this->next_attempt_at = $nextAttemptAt;
        }

        $this->save();
    }
}
