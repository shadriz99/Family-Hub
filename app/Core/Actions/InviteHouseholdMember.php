<?php

declare(strict_types=1);

namespace App\Core\Actions;

use App\Core\Enums\InvitationType;
use App\Core\Models\Household;
use App\Core\Models\HouseholdInvitation;
use App\Core\Support\IssuedInvitation;
use App\Mail\HouseholdInvitationMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

final class InviteHouseholdMember
{
    public const EXPIRES_AFTER_DAYS = 7;

    /**
     * Only the plaintext token leaves this action; the database keeps its SHA-256 hash.
     * The invitation is addressed to the partner's email; mailing it is a later slice.
     */
    public function handle(User $actor, Household $household, string $email): IssuedInvitation
    {
        $email = mb_strtolower(trim($email));

        if ($email === '' || mb_strlen($email) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw ValidationException::withMessages(['email' => __('household.email_invalid')]);
        }

        $issued = DB::transaction(function () use ($actor, $household, $email): IssuedInvitation {
            $household = Household::query()->lockForUpdate()->findOrFail($household->getKey());
            Gate::forUser($actor)->authorize('invitePartner', $household);

            if ($household->members()->lockForUpdate()->count() >= 2) {
                throw ValidationException::withMessages(['invitation' => __('household.invite_full')]);
            }

            $token = bin2hex(random_bytes(32));

            $invitation = new HouseholdInvitation([
                'email' => $email,
                'type' => InvitationType::Partner,
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

        // Sent synchronously so a mail failure surfaces to the caller; queue it with CORE-10.
        Mail::to($issued->invitation->email)->send(new HouseholdInvitationMail($issued));

        return $issued;
    }
}
