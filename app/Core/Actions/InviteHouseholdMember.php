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
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final class InviteHouseholdMember
{
    public const EXPIRES_AFTER_DAYS = 7;

    /** SEC-3 caps invitation issuance at ten per hour. */
    public const MAX_PER_HOUR = 10;

    public const THROTTLE_DECAY_SECONDS = 3600;

    /**
     * Only the plaintext token leaves this action; the database keeps its SHA-256 hash.
     * The invitation is addressed to the partner's email and mailed after the transaction.
     */
    public function handle(User $actor, Household $household, string $email): IssuedInvitation
    {
        $email = mb_strtolower(trim($email));

        if ($email === '' || mb_strlen($email) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw ValidationException::withMessages(['email' => __('household.email_invalid')]);
        }

        $throttleKey = 'core:invite-partner:'.$actor->getKey();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_PER_HOUR)) {
            throw ValidationException::withMessages(['email' => __('household.invite_throttled')]);
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

        // Only delivered invitations spend the hourly budget, so retyping a bad address
        // or a failed send does not lock the owner out.
        RateLimiter::hit($throttleKey, self::THROTTLE_DECAY_SECONDS);

        return $issued;
    }
}
