<?php

declare(strict_types=1);

namespace App\Mail;

use App\Core\Enums\InvitationType;
use App\Core\Support\IssuedInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class HouseholdInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public IssuedInvitation $issued)
    {
    }

    public function envelope(): Envelope
    {
        $invitation = $this->issued->invitation;
        $subject = $invitation->type === InvitationType::NewHousehold
            ? __('auth.mail_new_household_subject')
            : __('auth.mail_subject', ['household' => $this->householdName()]);

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $invitation = $this->issued->invitation;
        $household = $invitation->household;
        $timezone = $household->timezone ?? 'Asia/Jakarta';

        return new Content(
            markdown: 'mail.household-invitation',
            with: [
                'isNewHousehold' => $invitation->type === InvitationType::NewHousehold,
                'householdName' => $this->householdName(),
                'url' => route('invitation.show', $this->issued->token),
                'expiresAt' => $invitation->expires_at
                    ->setTimezone($timezone)
                    ->translatedFormat('j F Y H:i'),
            ],
        );
    }

    private function householdName(): string
    {
        return $this->issued->invitation->household->name ?? '';
    }
}
