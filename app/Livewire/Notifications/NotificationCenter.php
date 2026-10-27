<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class NotificationCenter extends Component
{
    public function markAsRead(string $id): void
    {
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        $notification = $user->notifications()->where('id', $id)->first();
        $notification?->markAsRead();
    }

    public function markAllAsRead(): void
    {
        $user = Auth::user();
        $user?->unreadNotifications()->update(['read_at' => now()]);
    }

    public function deleteNotification(string $id): void
    {
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        $user->notifications()->where('id', $id)->delete();
    }

    public function render(): View
    {
        $user = Auth::user();
        $notifications = $user !== null
            ? $user->notifications()->latest()->take(50)->get()
            : collect();

        $unreadCount = $user !== null ? $user->unreadNotifications()->count() : 0;

        return view('livewire.notifications.notification-center', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ])->layout('components.app-layout', ['title' => __('Pusat Notifikasi')]);
    }
}
