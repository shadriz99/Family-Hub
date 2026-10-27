<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Livewire\Reminders;

use App\Core\Enums\ReminderDeliveryStatus;
use App\Core\Models\ReminderDelivery;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class Index extends Component
{
    public function snoozeOneHour(string $deliveryId): void
    {
        $delivery = ReminderDelivery::query()->findOrFail($deliveryId);
        $delivery->remind_at = $delivery->remind_at->addHour();
        $delivery->save();
    }

    public function snoozeTomorrowMorning(string $deliveryId): void
    {
        $delivery = ReminderDelivery::query()->findOrFail($deliveryId);
        $delivery->remind_at = CarbonImmutable::tomorrow()->setHour(8)->setMinute(0);
        $delivery->save();
    }

    public function cancelDelivery(string $deliveryId): void
    {
        $delivery = ReminderDelivery::query()->findOrFail($deliveryId);
        $delivery->status = ReminderDeliveryStatus::Cancelled;
        $delivery->save();
    }

    public function render(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $household = app(HouseholdContext::class)->current();

        $deliveries = ReminderDelivery::query()
            ->with('reminder')
            ->where('household_id', $household->id)
            ->where('recipient_user_id', $user->id)
            ->where('status', ReminderDeliveryStatus::Pending)
            ->whereBetween('remind_at', [
                CarbonImmutable::now()->subHour(),
                CarbonImmutable::now()->addDays(14),
            ])
            ->orderBy('remind_at')
            ->get();

        return view('modules.organizer.reminders.index', [
            'deliveries' => $deliveries,
        ])->layout('layouts.app', ['title' => 'Pengingat Aktif']);
    }
}
