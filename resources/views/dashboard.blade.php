<x-app-layout :title="__('Beranda')">
    <div class="space-y-6">
        {{-- Greeting Header --}}
        <div>
            <h1 class="font-serif text-2xl font-bold tracking-tight sm:text-3xl">
                {{ __('Selamat datang, :name', ['name' => auth()->user()->name]) }}
            </h1>
            <p class="mt-1 text-sm text-[#637067] dark:text-[#b2beb2]">
                {{ $household ? $household->name : 'Family Hub' }}
            </p>
        </div>

        {{-- Core Dashboard Initial Grid Cards --}}
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
            {{-- Kartu Ringkasan Keuangan --}}
            <x-card :title="__('Ringkasan Keuangan')" :badge="!config('modules.finance') ? __('Segera') : null">
                @if (config('modules.finance'))
                    <p class="text-sm">Ringkasan keuangan aktif.</p>
                @else
                    <p class="text-sm leading-relaxed text-[#637067] dark:text-[#b2beb2]">
                        {{ __('Modul Keuangan (Phase 1) akan menampilkan saldo operasional, saldo tabungan, dan alokasi periode.') }}
                    </p>
                @endif
            </x-card>

            {{-- Agenda Hari Ini & Besok --}}
            <x-card :title="__('Hari Ini & Besok')" :badge="!config('modules.organizer') ? __('Segera') : null">
                @if (config('modules.organizer'))
                    <p class="text-sm">Agenda aktif.</p>
                @else
                    <p class="text-sm leading-relaxed text-[#637067] dark:text-[#b2beb2]">
                        {{ __('Modul Organizer (Phase 2) akan menggabungkan kalender keluarga, tugas rutin, dan daftar belanja.') }}
                    </p>
                @endif
            </x-card>

            {{-- Ringkasan Rumah Tangga --}}
            <x-card :title="__('Informasi Keluarga')">
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between border-b border-current/10 pb-2">
                        <span class="text-[#637067] dark:text-[#b2beb2]">{{ __('Zona Waktu') }}</span>
                        <span class="font-semibold">{{ $household?->timezone ?? 'Asia/Jakarta' }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-current/10 pb-2">
                        <span class="text-[#637067] dark:text-[#b2beb2]">{{ __('Hari Mulai Periode') }}</span>
                        <span class="font-semibold">{{ __('Tanggal :day', ['day' => $household?->finance_period_start_day ?? 1]) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[#637067] dark:text-[#b2beb2]">{{ __('Anggota Terdaftar') }}</span>
                        <span class="font-semibold">{{ $household?->members?->count() ?? 1 }} / 2</span>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
