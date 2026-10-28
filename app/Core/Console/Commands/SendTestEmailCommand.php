<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Mail\TestEmailMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Throwable;

final class SendTestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'core:send-test-email
                            {email? : Alamat email penerima untuk uji coba pengiriman}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim email uji fisik untuk memvalidasi konfigurasi driver SMTP / Mailer';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $recipient = (string) ($this->argument('email') ?? $this->ask('Masukkan alamat email penerima'));

        $validator = Validator::make(['email' => trim($recipient)], [
            'email' => ['required', 'string', 'email:rfc'],
        ]);

        if ($validator->fails()) {
            $this->error('Alamat email tidak valid: ' . $validator->errors()->first('email'));
            return self::FAILURE;
        }

        $email = mb_strtolower(trim($recipient));
        $mailer = (string) config('mail.default', 'log');
        $from = (string) config('mail.from.address', 'hello@example.com');
        $fromName = (string) config('mail.from.name', 'Family Hub');

        $this->info("Menyiapkan pengiriman email uji...");
        $this->line(" - Driver mailer : {$mailer}");
        $this->line(" - Pengirim       : {$fromName} <{$from}>");
        $this->line(" - Penerima       : {$email}");

        if ($mailer === 'smtp') {
            $host = (string) config('mail.mailers.smtp.host');
            $port = (string) config('mail.mailers.smtp.port');
            $this->line(" - SMTP Endpoint : {$host}:{$port}");
        }

        try {
            Mail::to($email)->send(new TestEmailMail($email));

            if ($mailer === 'log') {
                $this->warn("Catatan: Driver email aktif adalah 'log'. Email uji telah ditulis ke storage/logs/laravel.log.");
            } else {
                $this->info("Email uji berhasil dikirim ke {$email} via driver [{$mailer}].");
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Gagal mengirim email: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
