<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Core\Notifications\TestNotification;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class NotificationSettings extends Component
{
    public bool $notify_push = true;

    public bool $notify_email = true;

    public string $quiet_hours_start = '21:00';

    public string $quiet_hours_end = '06:00';

    public string $daily_digest_time = '06:00';

    public ?string $feedbackMessage = null;

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user !== null) {
            $this->notify_push = (bool) $user->notify_push;
            $this->notify_email = (bool) $user->notify_email;
            $this->quiet_hours_start = $this->formatTime($user->quiet_hours_start, '21:00');
            $this->quiet_hours_end = $this->formatTime($user->quiet_hours_end, '06:00');
            $this->daily_digest_time = $this->formatTime($user->daily_digest_time, '06:00');
        }
    }

    public function save(): void
    {
        $this->validate([
            'notify_push' => ['boolean'],
            'notify_email' => ['boolean'],
            'quiet_hours_start' => ['required', 'date_format:H:i'],
            'quiet_hours_end' => ['required', 'date_format:H:i'],
            'daily_digest_time' => ['required', 'date_format:H:i'],
        ]);

        /** @var User|null $user */
        $user = Auth::user();

        if ($user !== null) {
            $user->notify_push = $this->notify_push;
            $user->notify_email = $this->notify_email;
            $user->quiet_hours_start = $this->quiet_hours_start . ':00';
            $user->quiet_hours_end = $this->quiet_hours_end . ':00';
            $user->daily_digest_time = $this->daily_digest_time . ':00';
            $user->save();

            $this->feedbackMessage = __('Pengaturan notifikasi berhasil disimpan.');
        }
    }

    public function sendTestNotification(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user !== null) {
            $user->notify(new TestNotification());
            $this->feedbackMessage = __('Notifikasi uji berhasil dikirim ke saluran aktif Anda.');
        }
    }

    private function formatTime(?string $time, string $default): string
    {
        if ($time === null || trim($time) === '') {
            return $default;
        }

        $parts = explode(':', $time);

        return sprintf('%02d:%02d', (int) $parts[0], (int) ($parts[1] ?? 0));
    }

    public function render(): View
    {
        return view('livewire.settings.notification-settings')
            ->layout('components.app-layout', ['title' => __('Pengaturan Notifikasi')]);
    }
}
