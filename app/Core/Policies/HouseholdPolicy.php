<?php

declare(strict_types=1);

namespace App\Core\Policies;

use App\Core\Enums\MemberRole;
use App\Core\Models\Household;
use App\Models\User;

final class HouseholdPolicy
{
    public function view(User $user, Household $household): bool
    {
        return $this->role($user, $household) !== null;
    }

    public function update(User $user, Household $household): bool
    {
        return $this->view($user, $household);
    }

    public function invitePartner(User $user, Household $household): bool
    {
        return $this->role($user, $household) === MemberRole::Owner;
    }

    public function cancelInvitation(User $user, Household $household): bool
    {
        return $this->invitePartner($user, $household);
    }

    public function removePartner(User $user, Household $household): bool
    {
        return $this->invitePartner($user, $household);
    }

    public function transferOwnership(User $user, Household $household): bool
    {
        return $this->invitePartner($user, $household);
    }

    public function delete(User $user, Household $household): bool
    {
        return $this->invitePartner($user, $household);
    }

    public function leave(User $user, Household $household): bool
    {
        return $this->role($user, $household) === MemberRole::Partner;
    }

    private function role(User $user, Household $household): ?MemberRole
    {
        if (! $user->exists || ! $household->exists) {
            return null;
        }

        // Query current membership, not a potentially stale eager-loaded relation.
        return $user->householdMembership()
            ->where('household_id', $household->getKey())
            ->whereHas('household')
            ->first()?->role;
    }
}
