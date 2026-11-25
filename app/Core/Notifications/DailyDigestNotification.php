<?php

declare(strict_types=1);

namespace App\Core\Notifications;

use App\Core\Models\Household;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

final class DailyDigestNotification extends Notification
{
    use Queueable;

    /**
     * @param array{
     *     date: string,
     *     due_bills: array<int, array{name: string, amount: int, due_date: string, is_overdue: bool}>,
     *     tasks: array<int, array{title: string, priority: string, due_date: string, is_overdue: bool}>,
     *     asset_dues: array<int, array{title: string, type: string, due_date: string, is_overdue: bool}>,
     *     alerts: array<int, array{title: string, message: string, level: string}>
     * } $digestData
     */
    public function __construct(
        public readonly Household $household,
        public readonly array $digestData
    ) {
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
        $billCount = count($this->digestData['due_bills']);
        $taskCount = count($this->digestData['tasks']);
        $assetCount = count($this->digestData['asset_dues']);
        $alertCount = count($this->digestData['alerts']);

        $summaryParts = [];
        if ($billCount > 0) {
            $summaryParts[] = "{$billCount} tagihan";
        }
        if ($taskCount > 0) {
            $summaryParts[] = "{$taskCount} tugas";
        }
        if ($assetCount > 0) {
            $summaryParts[] = "{$assetCount} perawatan aset";
        }
        if ($alertCount > 0) {
            $summaryParts[] = "{$alertCount} peringatan";
        }

        $body = ! empty($summaryParts)
            ? 'Agenda hari ini: ' . implode(', ', $summaryParts) . '.'
            : 'Ringkasan harian keluarga telah diperbarui.';

        return [
            'type' => 'daily_digest',
            'household_id' => $this->household->id,
            'title' => __('Ringkasan Pagi Keluarga') . " ({$this->digestData['date']})",
            'body' => $body,
            'url' => '/',
            'data' => $this->digestData,
        ];
    }

    public function toWebPush(User $notifiable, Notification $notification): WebPushMessage
    {
        $dbData = $this->toDatabase($notifiable);

        return (new WebPushMessage())
            ->title($dbData['title'])
            ->body($dbData['body'])
            ->action(__('Buka Beranda'), 'view_dashboard')
            ->data(['url' => '/'])
            ->options(['TTL' => 43200]); // 12 hours
    }

    public function toMail(User $notifiable): MailMessage
    {
        $date = $this->digestData['date'];
        $mail = (new MailMessage())
            ->subject("[Family Hub] Ringkasan Pagi Keluarga - {$date}")
            ->greeting("Halo {$notifiable->name},")
            ->line("Berikut ringkasan agenda dan status penting rumah tangga {$this->household->name} untuk hari ini ({$date}):");

        if (! empty($this->digestData['due_bills'])) {
            $mail->line('**💳 Tagihan & Kewajiban Keuangan:**');
            foreach ($this->digestData['due_bills'] as $bill) {
                $status = $bill['is_overdue'] ? '⚠️ Terlambat' : 'Jatuh tempo hari ini';
                $formattedAmount = 'Rp ' . number_format($bill['amount'], 0, ',', '.');
                $mail->line("- {$bill['name']}: {$formattedAmount} ({$status})");
            }
        }

        if (! empty($this->digestData['tasks'])) {
            $mail->line('**📋 Tugas Rumah Tangga Hari Ini:**');
            foreach ($this->digestData['tasks'] as $task) {
                $status = $task['is_overdue'] ? '⚠️ Terlambat' : 'Hari ini';
                $mail->line("- {$task['title']} [Prioritas: {$task['priority']}] ({$status})");
            }
        }

        if (! empty($this->digestData['asset_dues'])) {
            $mail->line('**🛠️ Pemeliharaan & Dokumen Aset:**');
            foreach ($this->digestData['asset_dues'] as $asset) {
                $status = $asset['is_overdue'] ? '⚠️ Terlambat' : 'Jatuh tempo';
                $mail->line("- {$asset['title']} ({$asset['type']}) - {$status}");
            }
        }

        if (! empty($this->digestData['alerts'])) {
            $mail->line('**🔔 Peringatan Penting:**');
            foreach ($this->digestData['alerts'] as $alert) {
                $mail->line("- **{$alert['title']}**: {$alert['message']}");
            }
        }

        $mail->action('Buka Family Hub', url('/'))
            ->line('Semoga harimu menyenangkan dan produktif bersama keluarga!');

        return $mail;
    }
}
