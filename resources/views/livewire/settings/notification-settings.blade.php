<div class="mx-auto max-w-2xl space-y-6">
    <div>
        <h1 class="font-serif text-2xl font-bold tracking-tight sm:text-3xl">
            {{ __('Pengaturan Notifikasi') }}
        </h1>
        <p class="mt-1 text-sm text-[#637067] dark:text-[#b2beb2]">
            {{ __('Pilih saluran pemberitahuan, jam hening, dan waktu ringkasan harian keluarga Anda.') }}
        </p>
    </div>

    @include('livewire.settings.partials.navigation')

    @if ($feedbackMessage)
        <div class="rounded-2xl border border-[#28543e]/30 bg-[#28543e]/10 p-4 text-sm font-medium text-[#28543e] dark:text-[#bad6ae]" role="alert">
            {{ $feedbackMessage }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        {{-- Saluran Notifikasi --}}
        <x-card :title="__('Saluran Notifikasi')">
            <div class="space-y-4">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox"
                        wire:model="notify_push"
                        class="mt-1 size-4 rounded border-current/20 text-[#28543e] focus:ring-[#28543e]" />
                    <div class="space-y-0.5">
                        <span class="text-sm font-semibold text-current">{{ __('Notifikasi Web Push') }}</span>
                        <p class="text-xs text-[#637067] dark:text-[#b2beb2]">
                            {{ __('Menerima pemberitahuan langsung di browser HP atau laptop saat pengingat jatuh tempo.') }}
                        </p>
                    </div>
                </label>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox"
                        wire:model="notify_email"
                        class="mt-1 size-4 rounded border-current/20 text-[#28543e] focus:ring-[#28543e]" />
                    <div class="space-y-0.5">
                        <span class="text-sm font-semibold text-current">{{ __('Notifikasi Email') }}</span>
                        <p class="text-xs text-[#637067] dark:text-[#b2beb2]">
                            {{ __('Menerima email untuk pengingat penting dan ringkasan aktivitas keluarga.') }}
                        </p>
                    </div>
                </label>
            </div>
        </x-card>

        {{-- Jam Hening (Quiet Hours) --}}
        <x-card :title="__('Jam Hening (Quiet Hours)')">
            <p class="mb-4 text-xs leading-relaxed text-[#637067] dark:text-[#b2beb2]">
                {{ __('Selama jam hening, pengingat baru tidak akan mengganggu Anda dan secara otomatis dijadwalkan ulang ke akhir jam hening.') }}
            </p>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="quiet_hours_start" class="block text-xs font-semibold text-current/80">
                        {{ __('Mulai Jam Hening') }}
                    </label>
                    <input type="time"
                        id="quiet_hours_start"
                        wire:model="quiet_hours_start"
                        class="mt-1 w-full rounded-2xl border border-current/15 bg-[#fffefa]/80 px-3 py-2 text-sm text-current dark:bg-[#151f1b]" />
                    @error('quiet_hours_start') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="quiet_hours_end" class="block text-xs font-semibold text-current/80">
                        {{ __('Selesai Jam Hening') }}
                    </label>
                    <input type="time"
                        id="quiet_hours_end"
                        wire:model="quiet_hours_end"
                        class="mt-1 w-full rounded-2xl border border-current/15 bg-[#fffefa]/80 px-3 py-2 text-sm text-current dark:bg-[#151f1b]" />
                    @error('quiet_hours_end') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
        </x-card>

        {{-- Ringkasan Harian --}}
        <x-card :title="__('Waktu Ringkasan Harian')">
            <div class="space-y-2">
                <label for="daily_digest_time" class="block text-xs font-semibold text-current/80">
                    {{ __('Jam Pengiriman Ringkasan Harian') }}
                </label>
                <input type="time"
                    id="daily_digest_time"
                    wire:model="daily_digest_time"
                    class="w-full sm:w-48 rounded-2xl border border-current/15 bg-[#fffefa]/80 px-3 py-2 text-sm text-current dark:bg-[#151f1b]" />
                @error('daily_digest_time') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
        </x-card>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between pt-2">
            <button type="button"
                wire:click="sendTestNotification"
                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-current/20 px-4 py-2.5 text-sm font-semibold transition-colors hover:bg-current/5">
                <x-heroicon-o-paper-airplane class="size-4" />
                <span>{{ __('Kirim Notifikasi Uji') }}</span>
            </button>

            <button type="submit"
                class="inline-flex items-center justify-center rounded-2xl bg-[#28543e] px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:opacity-90 dark:bg-[#bad6ae] dark:text-[#151f1b]">
                {{ __('Simpan Pengaturan') }}
            </button>
        </div>
    </form>
</div>
