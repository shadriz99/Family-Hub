<?php

declare(strict_types=1);

namespace App\Core\Actions;

use App\Core\Enums\InvitationType;
use App\Core\Models\Household;
use App\Core\Models\HouseholdInvitation;
use App\Core\Models\HouseholdMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AcceptNewHouseholdInvitation
{
    /**
     * Accept a new_household invitation, bootstrapping a household with the user as owner.
     */
    public function handle(User $user, string $token, string $householdName): Household
    {
        $hash = hash('sha256', $token);

        return DB::transaction(function () use ($user, $hash, $householdName): Household {
            $invitation = HouseholdInvitation::query()
                ->where('token_hash', $hash)
                ->lockForUpdate()
                ->first();

            if ($invitation === null || $invitation->revoked_at !== null) {
                throw $this->reject('household.invitation_invalid');
            }

            if ($invitation->accepted_at !== null) {
                throw $this->reject('household.invitation_used');
            }

            if ($invitation->type !== InvitationType::NewHousehold) {
                throw $this->reject('household.invitation_invalid');
            }

            if (strcasecmp((string) $user->email, $invitation->email) !== 0) {
                throw $this->reject('household.invitation_email_mismatch');
            }

            if ($user->is_system_admin) {
                throw $this->reject('household.admin_not_member');
            }

            if ($invitation->expires_at->isPast()) {
                throw $this->reject('household.invitation_expired');
            }

            $existing = HouseholdMember::query()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                throw $this->reject('household.already_member');
            }

            $household = app(CreateHousehold::class)->handle($user, $householdName);

            $invitation->accepted_at = now()->toImmutable();
            $invitation->save();

            activity()->performedOn($household)->causedBy($user)
                ->withProperties(['invitation_id' => $invitation->getKey()])
                ->log('core.household.owner_joined');

            return $household;
        });
    }

    private function reject(string $key): ValidationException
    {
        return ValidationException::withMessages(['invitation' => __($key)]);
    }
}
