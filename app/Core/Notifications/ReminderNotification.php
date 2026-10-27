<?php

declare(strict_types=1);

namespace App\Core\Notifications;

use App\Core\Models\ReminderDelivery;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

final class ReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly ReminderDelivery $delivery)
    {
    }

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
        $reminder = $this->delivery->reminder;

        return [
            'reminder_id' => $reminder?->id,
            'delivery_id' => $this->delivery->id,
            'title' => $reminder !== null ? $reminder->title : __('Pengingat'),
            'body' => $reminder?->body,
            'url' => $reminder !== null ? $reminder->url : '/',
            'remind_at' => $this->delivery->remind_at->toIso8601String(),
        ];
    }

    public function toWebPush(User $notifiable, Notification $notification): WebPushMessage
    {
        $reminder = $this->delivery->reminder;
        $title = $reminder !== null ? $reminder->title : __('Pengingat Family Hub');
        $body = $reminder !== null && $reminder->body !== null ? $reminder->body : '';
        $url = $reminder !== null ? $reminder->url : '/';

        return (new WebPushMessage())
            ->title($title)
            ->body($body)
            ->data(['url' => $url])
            ->action(__('Buka'), 'open_url');
    }

    public function toMail(User $notifiable): MailMessage
    {
        $reminder = $this->delivery->reminder;
        $title = $reminder !== null ? $reminder->title : __('Pengingat Family Hub');
        $body = $reminder?->body;
        $url = $reminder !== null ? $reminder->url : '/';

        $mail = (new MailMessage())
            ->subject($title)
            ->greeting(__('Halo, :name', ['name' => $notifiable->name]))
            ->line($title);

        if ($body !== null && $body !== '') {
            $mail->line($body);
        }

        return $mail->action(__('Lihat di Family Hub'), url($url));
    }
}
