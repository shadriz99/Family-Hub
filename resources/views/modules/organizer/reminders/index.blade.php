<div class="space-y-6 max-w-4xl mx-auto pb-16">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Pengingat Aktif</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Daftar notifikasi & pengingat yang akan datang untuk Anda dalam 14 hari ke depan.</p>
        </div>
    </div>

    <!-- Reminders List -->
    <div class="space-y-3">
        @forelse ($deliveries as $del)
            <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">⏰</span>
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                            {{ $del->reminder?->title ?: 'Pengingat' }}
                        </h3>
                        @if ($del->reminder?->body)
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $del->reminder->body }}</p>
                        @endif
                        <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400 block mt-1">
                            📅 Dijadwalkan: {{ $del->remind_at->translatedFormat('d M Y, H:i') }} ({{ $del->remind_at->diffForHumans() }})
                        </span>
                    </div>
                </div>

                <!-- Actions: Snooze / Cancel -->
                <div class="flex items-center gap-2 self-end sm:self-auto text-xs">
                    <button type="button" wire:click="snoozeOneHour('{{ $del->id }}')" class="px-3 py-1.5 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300">
                        +1 Jam
                    </button>
                    <button type="button" wire:click="snoozeTomorrowMorning('{{ $del->id }}')" class="px-3 py-1.5 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300">
                        Besok 08:00
                    </button>
                    <button type="button" wire:click="cancelDelivery('{{ $del->id }}')" class="px-3 py-1.5 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-xl">
                        Batalkan
                    </button>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-slate-800 p-12 text-center rounded-2xl border border-slate-200 dark:border-slate-700">
                <p class="text-sm text-slate-500 dark:text-slate-400">Tidak ada pengingat yang menunggu untuk 14 hari ke depan.</p>
            </div>
        @endforelse
    </div>
</div>
