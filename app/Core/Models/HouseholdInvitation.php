<?php

declare(strict_types=1);

namespace App\Core\Models;

use App\Core\Enums\InvitationType;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Invitation to join a household, or to create one (masterplan §2.3 and §5.1).
 *
 * Deliberately not using BelongsToHousehold: an invitation can exist before its household
 * (new-household flow) and acceptance happens before the user has a household, so lookups
 * by token must work without an ambient tenant context.
 *
 * @property string $email
 * @property InvitationType $type
 * @property string $token_hash
 * @property \Carbon\CarbonImmutable $expires_at
 * @property \Carbon\CarbonImmutable|null $accepted_at
 * @property \Carbon\CarbonImmutable|null $revoked_at
 */
class HouseholdInvitation extends Model
{
    use HasUlids;

    protected $fillable = ['email', 'type', 'token_hash', 'expires_at'];

    protected function casts(): array
    {
        return [
            'type' => InvitationType::class,
            'expires_at' => 'immutable_datetime',
            'accepted_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /** @return BelongsTo<User, $this> */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
