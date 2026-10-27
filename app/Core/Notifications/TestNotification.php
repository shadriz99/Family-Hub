<?php

declare(strict_types=1);

namespace App\Core\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

final class TestNotification extends Notification
{
    use Queueable;

    /**
     * @return array<int, string|class-string>
     */
    public function via(User $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->notify_push) {
            $channels[] = WebPushChannel::class;
        }

        if ($notifiable->notify_email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(User $notifiable): array
    {
        return [
            'title' => __('Uji Notifikasi Berhasil'),
            'body' => __('Ini adalah notifikasi uji coba dari Family Hub untuk memastikan jalur notifikasi aktif.'),
            'url' => route('settings.notifications'),
            'type' => 'test',
        ];
    }

    public function toWebPush(User $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage())
            ->title(__('Uji Notifikasi Family Hub'))
            ->body(__('Notifikasi push browser Anda aktif dan berfungsi dengan baik.'))
            ->data(['url' => route('settings.notifications')])
            ->action(__('Buka'), 'open_url');
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('Uji Notifikasi Family Hub'))
            ->greeting(__('Halo, :name', ['name' => $notifiable->name]))
            ->line(__('Ini adalah email notifikasi uji coba untuk memverifikasi pengaturan email Anda.'))
            ->action(__('Buka Pengaturan Notifikasi'), route('settings.notifications'));
    }
}
