<?php

declare(strict_types=1);

namespace App\Core\Actions;

use App\Core\Enums\InvitationType;
use App\Core\Models\HouseholdInvitation;
use App\Core\Support\IssuedInvitation;
use App\Mail\HouseholdInvitationMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final class InviteNewHousehold
{
    public const EXPIRES_AFTER_DAYS = 7;

    /** SEC-3 caps invitation issuance at ten per hour. */
    public const MAX_PER_HOUR = 10;

    public const THROTTLE_DECAY_SECONDS = 3600;

    /**
     * Issues an invitation to create a new household. Only system administrators can call this.
     */
    public function handle(User $actor, string $email): IssuedInvitation
    {
        if (! $actor->is_system_admin) {
            throw ValidationException::withMessages(['admin' => __('household.unauthorized')]);
        }

        $email = mb_strtolower(trim($email));

        if ($email === '' || mb_strlen($email) > 255 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw ValidationException::withMessages(['email' => __('household.email_invalid')]);
        }

        $throttleKey = 'core:invite-new-household:'.$actor->getKey();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_PER_HOUR)) {
            throw ValidationException::withMessages(['email' => __('household.invite_throttled')]);
        }

        $issued = DB::transaction(function () use ($actor, $email): IssuedInvitation {
            // Revoke any previous pending new-household invitation for this email
            HouseholdInvitation::query()
                ->where('type', InvitationType::NewHousehold)
                ->where('email', $email)
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->get()
                ->each(function (HouseholdInvitation $previous): void {
                    $previous->revoked_at = now()->toImmutable();
                    $previous->save();
                });

            $token = bin2hex(random_bytes(32));

            $invitation = new HouseholdInvitation([
                'email' => $email,
                'type' => InvitationType::NewHousehold,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addDays(self::EXPIRES_AFTER_DAYS),
            ]);
            $invitation->invitedBy()->associate($actor);
            $invitation->save();

            activity()->causedBy($actor)
                ->withProperties(['invitation_id' => $invitation->getKey(), 'email' => $email])
                ->log('core.household.new_household_invited');

            return new IssuedInvitation($invitation, $token);
        });

        Mail::to($issued->invitation->email)->send(new HouseholdInvitationMail($issued));

        RateLimiter::hit($throttleKey, self::THROTTLE_DECAY_SECONDS);

        return $issued;
    }
}
