<?php

declare(strict_types=1);

namespace App\Core\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TwoFactorOtpNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $otpCode)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("[Family Hub] Kode Verifikasi Masuk (OTP): {$this->otpCode}")
            ->greeting("Halo, {$notifiable->name}")
            ->line('Berikut adalah kode verifikasi (OTP) untuk masuk ke akun Family Hub Anda:')
            ->line("# **{$this->otpCode}**")
            ->line('Kode ini hanya berlaku selama 10 menit.')
            ->line('Jangan pernah membagikan kode verifikasi ini kepada siapapun.')
            ->line('Jika Anda tidak merasa mencoba masuk, segera amankan akun dan ubah kata sandi Anda.');
    }
}
