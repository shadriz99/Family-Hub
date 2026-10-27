<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Core\Models\HouseholdMember;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

/**
 * @property \Illuminate\Support\Carbon|null $disabled_at
 */
#[Fillable(['name', 'email', 'password', 'locale', 'notify_push', 'notify_email', 'quiet_hours_start', 'quiet_hours_end', 'daily_digest_time', 'disabled_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use HasPushSubscriptions;
    use HasUlids;
    use Notifiable;

    /** @return HasOne<HouseholdMember, $this> */
    public function householdMembership(): HasOne
    {
        return $this->hasOne(HouseholdMember::class);
    }

    public function isDisabled(): bool
    {
        return $this->disabled_at !== null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_system_admin' => 'boolean',
            'disabled_at' => 'datetime',
            'notify_push' => 'boolean',
            'notify_email' => 'boolean',
        ];
    }
}
