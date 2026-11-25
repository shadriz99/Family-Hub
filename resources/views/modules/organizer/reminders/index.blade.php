<div class="space-y-6 max-w-4xl mx-auto pb-16">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">Pengingat Aktif</h1>
            <p class="text-sm text-[#637067] dark:text-[#637067]">Daftar notifikasi & pengingat yang akan datang untuk Anda dalam 14 hari ke depan.</p>
        </div>
    </div>

    <!-- Reminders List -->
    <div class="space-y-3">
        @forelse ($deliveries as $del)
            <div class="bg-[#fffefa] dark:bg-[#1f2c25] p-4 rounded-2xl shadow-sm border border-current/10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">⏰</span>
                    <div>
                        <h3 class="text-base font-semibold text-[#263c32] dark:text-[#f3f1e8]">
                            {{ $del->reminder?->title ?: 'Pengingat' }}
                        </h3>
                        @if ($del->reminder?->body)
                            <p class="text-xs text-[#637067] dark:text-[#637067] mt-0.5">{{ $del->reminder->body }}</p>
                        @endif
                        <span class="text-xs font-medium text-[#28543e] dark:text-[#bad6ae] block mt-1">
                            <x-heroicon-o-calendar class="mr-1 inline-block size-4 align-text-bottom" /> Dijadwalkan: {{ $del->remind_at->translatedFormat('d M Y, H:i') }} ({{ $del->remind_at->diffForHumans() }})
                        </span>
                    </div>
                </div>

                <!-- Actions: Snooze / Cancel -->
                <div class="flex items-center gap-2 self-end sm:self-auto text-xs">
                    <button type="button" wire:click="snoozeOneHour('{{ $del->id }}')" class="px-3 py-1.5 border border-current/10 rounded-xl hover:bg-current/5 text-[#263c32] dark:text-[#b2beb2]">
                        +1 Jam
                    </button>
                    <button type="button" wire:click="snoozeTomorrowMorning('{{ $del->id }}')" class="px-3 py-1.5 border border-current/10 rounded-xl hover:bg-current/5 text-[#263c32] dark:text-[#b2beb2]">
                        Besok 08:00
                    </button>
                    <button type="button" wire:click="cancelDelivery('{{ $del->id }}')" class="px-3 py-1.5 text-[#ad3f45] dark:text-[#ffafb1] hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-xl">
                        Batalkan
                    </button>
                </div>
            </div>
        @empty
            <div class="bg-[#fffefa] dark:bg-[#1f2c25] p-12 text-center rounded-2xl border border-current/10">
                <p class="text-sm text-[#637067] dark:text-[#637067]">Tidak ada pengingat yang menunggu untuk 14 hari ke depan.</p>
            </div>
        @endforelse
    </div>
</div>
