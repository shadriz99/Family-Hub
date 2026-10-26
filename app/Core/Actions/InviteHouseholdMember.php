<?php

declare(strict_types=1);

namespace App\Core\Actions;

use App\Core\Models\Household;
use App\Core\Models\HouseholdInvitation;
use App\Core\Support\IssuedInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class InviteHouseholdMember
{
    public const EXPIRES_AFTER_DAYS = 7;

    /**
     * Only the plaintext token leaves this action; the database keeps its SHA-256 hash.
     */
    public function handle(User $actor, Household $household): IssuedInvitation
    {
        return DB::transaction(function () use ($actor, $household): IssuedInvitation {
            $household = Household::query()->lockForUpdate()->findOrFail($household->getKey());
            Gate::forUser($actor)->authorize('invitePartner', $household);

            if ($household->members()->lockForUpdate()->count() >= 2) {
                throw ValidationException::withMessages(['invitation' => __('household.invite_full')]);
            }

            $token = bin2hex(random_bytes(32));

            $invitation = new HouseholdInvitation([
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addDays(self::EXPIRES_AFTER_DAYS),
            ]);
            $invitation->household()->associate($household);
            $invitation->invitedBy()->associate($actor);
            $invitation->save();

            activity()->performedOn($household)->causedBy($actor)
                ->withProperties(['invitation_id' => $invitation->getKey()])
                ->log('core.household.partner_invited');

            return new IssuedInvitation($invitation, $token);
        });
    }
}
