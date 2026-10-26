<?php

declare(strict_types=1);

namespace App\Core\Models;

use App\Core\Enums\MemberRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property MemberRole $role
 */
class HouseholdMember extends Model
{
    use HasUlids;

    protected $fillable = ['role', 'color', 'joined_at', 'slot'];

    protected function casts(): array
    {
        return ['role' => MemberRole::class, 'joined_at' => 'immutable_datetime', 'slot' => 'integer'];
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
