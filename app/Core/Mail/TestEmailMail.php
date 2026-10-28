<?php

declare(strict_types=1);

namespace App\Core\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class TestEmailMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly string $recipientEmail)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Uji Pengiriman Email — Family Hub',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<h2>Halo dari Family Hub!</h2><p>Ini adalah email uji untuk memverifikasi bahwa konfigurasi SMTP produksi Anda telah berjalan dengan sukses.</p><p>Waktu pengiriman: <strong>' . now()->toIso8601String() . '</strong></p>',
        );
    }
}
