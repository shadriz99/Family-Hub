<div class="mx-auto max-w-xl space-y-6">
    <div>
        <h1 class="font-serif text-2xl font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">
            {{ __('Tetapkan Periode Keuangan') }}
        </h1>
        <p class="mt-1 text-sm text-[#637067] dark:text-[#b2beb2]">
            {{ __('Menu Keuangan terkunci hingga tanggal mulai dan selesai periode aktif ditetapkan.') }}
        </p>
    </div>

    @if ($previousStart && $previousEnd)
        <div class="flex items-start gap-3 rounded-2xl border border-[#87571f]/25 bg-[#87571f]/10 p-4 text-sm text-[#87571f] dark:border-[#edc48b]/30 dark:bg-[#edc48b]/10 dark:text-[#edc48b]" role="status">
            <x-heroicon-o-lock-closed class="mt-0.5 size-5 shrink-0" />
            <div>
                <p class="font-semibold">{{ __('Periode Sebelumnya Telah Berakhir') }}</p>
                <p class="mt-0.5 text-xs opacity-90">
                    {{ __('Periode :start – :end sudah selesai. Silakan tetapkan tanggal mulai dan selesai periode baru untuk membuka kembali menu Keuangan.', ['start' => $previousStart, 'end' => $previousEnd]) }}
                </p>
            </div>
        </div>
    @elseif (! $isAlreadyActive)
        <div class="flex items-start gap-3 rounded-2xl border border-[#87571f]/25 bg-[#87571f]/10 p-4 text-sm text-[#87571f] dark:border-[#edc48b]/30 dark:bg-[#edc48b]/10 dark:text-[#edc48b]" role="status">
            <x-heroicon-o-lock-closed class="mt-0.5 size-5 shrink-0" />
            <div>
                <p class="font-semibold">{{ __('Menu Keuangan Terkunci') }}</p>
                <p class="mt-0.5 text-xs opacity-90">
                    {{ __('Belum ada periode keuangan aktif untuk hari ini. Tetapkan tanggal mulai dan selesai periode di bawah ini.') }}
                </p>
            </div>
        </div>
    @endif

    <x-card :title="__('Rentang Periode Baru')">
        <form wire:submit="save" class="space-y-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="period-start-date" class="mb-2 block text-sm font-medium text-[#263c32] dark:text-[#f3f1e8]">
                        {{ __('Tanggal Mulai') }}
                    </label>
                    <input id="period-start-date"
                        type="date"
                        wire:model="start_date"
                        required
                        class="min-h-11 w-full rounded-xl border border-current/25 bg-transparent px-3 text-sm text-current focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#28543e] dark:focus-visible:outline-[#bad6ae]" />
                    @error('start_date')
                        <p class="mt-1.5 text-xs font-medium text-[#ad3f45] dark:text-[#ffafb1]" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="period-end-date" class="mb-2 block text-sm font-medium text-[#263c32] dark:text-[#f3f1e8]">
                        {{ __('Tanggal Selesai') }}
                    </label>
                    <input id="period-end-date"
                        type="date"
                        wire:model="end_date"
                        required
                        class="min-h-11 w-full rounded-xl border border-current/25 bg-transparent px-3 text-sm text-current focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#28543e] dark:focus-visible:outline-[#bad6ae]" />
                    @error('end_date')
                        <p class="mt-1.5 text-xs font-medium text-[#ad3f45] dark:text-[#ffafb1]" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                <a href="{{ url('/') }}"
                    class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-current/20 px-4 py-2.5 text-sm font-semibold text-current transition-colors hover:bg-current/5">
                    {{ __('Kembali ke Beranda') }}
                </a>
                <button type="submit"
                    wire:loading.attr="disabled"
                    x-bind:disabled="$store.network?.isOffline"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-2xl bg-[#28543e] px-5 py-2.5 text-sm font-semibold text-white shadow-xs transition-opacity hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-[#bad6ae] dark:text-[#151f1b]">
                    <x-heroicon-o-lock-open class="size-4" />
                    <span>{{ __('Simpan & Buka Keuangan') }}</span>
                </button>
            </div>
        </form>
    </x-card>
</div>
