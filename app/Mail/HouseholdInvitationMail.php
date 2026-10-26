<?php

declare(strict_types=1);

namespace App\Mail;

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
        return new Envelope(
            subject: __('auth.mail_subject', ['household' => $this->householdName()]),
        );
    }

    public function content(): Content
    {
        $invitation = $this->issued->invitation;
        $household = $invitation->household;

        return new Content(
            markdown: 'mail.household-invitation',
            with: [
                'householdName' => $this->householdName(),
                'url' => route('invitation.show', $this->issued->token),
                'expiresAt' => $invitation->expires_at
                    ->setTimezone($household->timezone ?? 'Asia/Jakarta')
                    ->translatedFormat('j F Y H:i'),
            ],
        );
    }

    private function householdName(): string
    {
        return $this->issued->invitation->household->name;
    }
}
