<?php

declare(strict_types=1);

namespace App\Core\Models;

use App\Core\Enums\ReminderStatus;
use App\Core\Support\BelongsToHousehold;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Logical reminder for a specific remindable entity within a household.
 *
 * @property string $id
 * @property string $household_id
 * @property string $remindable_type
 * @property string $remindable_id
 * @property string $key
 * @property string $title
 * @property string|null $body
 * @property string $url
 * @property CarbonImmutable $remind_at
 * @property string|null $recipient_user_id
 * @property ReminderStatus $status
 * @property string|null $created_by
 */
class Reminder extends Model
{
    use BelongsToHousehold;
    use HasUlids;

    protected $fillable = [
        'household_id',
        'remindable_type',
        'remindable_id',
        'key',
        'title',
        'body',
        'url',
        'remind_at',
        'recipient_user_id',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReminderStatus::class,
            'remind_at' => 'immutable_datetime',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function remindable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<ReminderDelivery, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(ReminderDelivery::class);
    }

    public function isForHousehold(): bool
    {
        return $this->recipient_user_id === null;
    }

    public function isScheduled(): bool
    {
        return $this->status === ReminderStatus::Scheduled;
    }

    public function isCancelled(): bool
    {
        return $this->status === ReminderStatus::Cancelled;
    }
}
