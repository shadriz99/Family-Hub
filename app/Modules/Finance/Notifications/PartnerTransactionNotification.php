<?php

declare(strict_types=1);

namespace App\Modules\Finance\Notifications;

use App\Core\Support\Money;
use App\Models\User;
use App\Modules\Finance\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

final class PartnerTransactionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Transaction $transaction,
        public User $recordedBy,
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
        $amountStr = Money::format($this->transaction->amount);
        $typeLabel = $this->transaction->type->label();
        $categoryName = $this->transaction->category !== null ? $this->transaction->category->name : 'Transaksi';

        return [
            'title' => "{$this->recordedBy->name} mencatat {$typeLabel}",
            'body' => "{$categoryName} sejumlah {$amountStr}",
            'url' => route('finance.transactions.show', $this->transaction->id),
            'type' => 'finance_partner_transaction',
        ];
    }

    public function toWebPush(User $notifiable, Notification $notification): WebPushMessage
    {
        $amountStr = Money::format($this->transaction->amount);
        $typeLabel = $this->transaction->type->label();
        $categoryName = $this->transaction->category !== null ? $this->transaction->category->name : 'Transaksi';

        return (new WebPushMessage())
            ->title("{$this->recordedBy->name} mencatat {$typeLabel}")
            ->body("{$categoryName} sejumlah {$amountStr}")
            ->action('Lihat', 'view_transaction')
            ->data(['url' => route('finance.transactions.show', $this->transaction->id)]);
    }

    public function toMail(User $notifiable): MailMessage
    {
        $amountStr = Money::format($this->transaction->amount);
        $typeLabel = $this->transaction->type->label();
        $categoryName = $this->transaction->category !== null ? $this->transaction->category->name : 'Transaksi';

        return (new MailMessage())
            ->subject("Aktivitas Keuangan Baru dari {$this->recordedBy->name}")
            ->greeting("Halo {$notifiable->name},")
            ->line("{$this->recordedBy->name} baru saja mencatat {$typeLabel} {$categoryName} sebesar {$amountStr}.")
            ->action('Lihat Transaksi', route('finance.transactions.show', $this->transaction->id));
    }
}
