<?php

declare(strict_types=1);

namespace App\Core\Actions;

use App\Core\Models\Household;
use App\Core\Models\HouseholdInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class RevokeHouseholdInvitation
{
    public function handle(User $actor, Household $household, HouseholdInvitation $invitation): void
    {
        DB::transaction(function () use ($actor, $household, $invitation): void {
            $household = Household::query()->lockForUpdate()->findOrFail($household->getKey());
            Gate::forUser($actor)->authorize('cancelInvitation', $household);

            $pending = $household->invitations()->whereKey($invitation->getKey())->lockForUpdate()->first();

            if ($pending === null) {
                throw ValidationException::withMessages(['invitation' => __('household.invitation_invalid')]);
            }

            if ($pending->accepted_at !== null) {
                throw ValidationException::withMessages(['invitation' => __('household.invitation_used')]);
            }

            $pending->delete();

            activity()->performedOn($household)->causedBy($actor)
                ->withProperties(['invitation_id' => $pending->getKey()])
                ->log('core.household.invitation_revoked');
        });
    }
}
