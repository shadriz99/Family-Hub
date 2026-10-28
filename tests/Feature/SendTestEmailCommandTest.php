<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Mail\TestEmailMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class SendTestEmailCommandTest extends TestCase
{
    public function test_sends_test_email_successfully_via_artisan(): void
    {
        Mail::fake();

        $this->artisan('core:send-test-email', ['email' => 'operator@example.com'])
            ->expectsOutputToContain('Driver mailer')
            ->expectsOutputToContain('operator@example.com')
            ->assertExitCode(0);

        Mail::assertSent(TestEmailMail::class, function (TestEmailMail $mail): bool {
            return $mail->hasTo('operator@example.com');
        });
    }

    public function test_rejects_invalid_email_format(): void
    {
        $this->artisan('core:send-test-email', ['email' => 'bukan-email-valid'])
            ->expectsOutputToContain('Alamat email tidak valid')
            ->assertExitCode(1);
    }
}
