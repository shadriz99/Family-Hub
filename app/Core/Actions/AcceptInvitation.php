<?php

declare(strict_types=1);

namespace App\Core\Actions;

use App\Core\Enums\InvitationType;
use App\Core\Enums\MemberRole;
use App\Core\Models\Household;
use App\Core\Models\HouseholdInvitation;
use App\Core\Models\HouseholdMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AcceptInvitation
{
    /**
     * Admit the invited user as the partner, assigning the free member slot atomically.
     */
    public function handle(User $user, string $token): HouseholdMember
    {
        $hash = hash('sha256', $token);

        return DB::transaction(function () use ($user, $hash): HouseholdMember {
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

            // The new-household flow creates an owner and belongs to its own slice.
            if ($invitation->type !== InvitationType::Partner) {
                throw $this->reject('household.invitation_invalid');
            }

            $household = Household::query()->lockForUpdate()->find($invitation->household_id);

            if ($household === null) {
                throw $this->reject('household.invitation_invalid');
            }

            if ($invitation->expires_at->isPast()) {
                throw $this->reject('household.invitation_expired');
            }

            $membership = HouseholdMember::query()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->first();

            if ($membership !== null) {
                throw $this->reject('household.already_member');
            }

            $freeSlots = array_values(array_diff([1, 2], $household->members()->lockForUpdate()->pluck('slot')->all()));

            if ($freeSlots === []) {
                throw $this->reject('household.invite_full');
            }

            $member = new HouseholdMember([
                'role' => MemberRole::Partner,
                'color' => MemberRole::Partner->color(),
                'slot' => $freeSlots[0],
                'joined_at' => now(),
            ]);
            $member->user()->associate($user);
            $household->members()->save($member);

            $invitation->accepted_at = now()->toImmutable();
            $invitation->save();

            activity()->performedOn($household)->causedBy($user)
                ->withProperties(['member_user_id' => $user->getKey(), 'slot' => $member->slot])
                ->log('core.household.partner_joined');

            return $member;
        });
    }

    private function reject(string $key): ValidationException
    {
        return ValidationException::withMessages(['invitation' => __($key)]);
    }
}
