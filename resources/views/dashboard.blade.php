<x-app-layout :title="__('Beranda')">
    <div class="space-y-6">
        {{-- 1. Greeting Header --}}
        <div>
            <h1 class="font-serif text-2xl font-bold tracking-tight sm:text-3xl">
                {{ $greeting ?? __('Selamat datang') }}
            </h1>
            <p class="mt-1 text-sm text-[#637067] dark:text-[#b2beb2]">
                {{ $household ? $household->name : 'Family Hub' }}
            </p>
        </div>

        {{-- 2. Alerts Block (AlertAggregator, maks 5) --}}
        @if (!empty($alerts))
            <div class="space-y-2">
                @foreach ($alerts as $alert)
                    @php
                        $badgeColor = match ($alert->level) {
                            'danger' => 'danger',
                            'warning' => 'warning',
                            default => 'info',
                        };
                    @endphp
                    <div class="flex items-center justify-between gap-3 rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-xs dark:bg-[#1f2c25]">
                        <div class="flex items-center gap-3 min-w-0">
                            <x-badge-status :color="$badgeColor">
                                {{ strtoupper($alert->level) }}
                            </x-badge-status>
                            <span class="truncate text-sm font-medium text-current">
                                {{ $alert->title }}
                            </span>
                        </div>
                        @if ($alert->url)
                            <a href="{{ $alert->url }}" class="shrink-0 text-xs font-semibold text-[#059669] hover:underline dark:text-[#10b981]">
                                {{ __('Buka') }} &rarr;
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- 3. Dynamic Module Widgets (hanya dirender jika modul aktif) --}}
        @if (!empty($widgets))
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($widgets as $widget)
                    {!! $widget->render($household) !!}
                @endforeach
            </div>
        @endif

        {{-- 4 & 5. Calendar Grids: Hari Ini & Besok + 7 Hari ke Depan --}}
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            {{-- Hari Ini & Besok --}}
            <x-card :title="__('Hari Ini & Besok')">
                @if (empty($todayAndTomorrow))
                    <p class="text-sm leading-relaxed text-[#637067] dark:text-[#b2beb2]">
                        {{ __('Tidak ada agenda atau tagihan untuk hari ini dan besok.') }}
                    </p>
                @else
                    <div class="divide-y divide-current/10">
                        @foreach ($todayAndTomorrow as $item)
                            <div class="flex items-center justify-between py-2.5 text-sm">
                                <div class="min-w-0">
                                    <p class="font-medium text-current truncate">{{ $item->title }}</p>
                                    <p class="text-xs text-[#637067] dark:text-[#b2beb2]">
                                        <x-datetime :value="$item->start" :household="$household" />
                                    </p>
                                </div>
                                <span class="rounded-full bg-current/10 px-2 py-0.5 text-xs text-current/80">
                                    {{ $item->sourceKey }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>

            {{-- 7 Hari ke Depan --}}
            <x-card :title="__('7 Hari ke Depan')">
                @if (empty($upcomingWeek))
                    <p class="text-sm leading-relaxed text-[#637067] dark:text-[#b2beb2]">
                        {{ __('Tidak ada agenda terjadwal untuk 7 hari ke depan.') }}
                    </p>
                @else
                    <div class="divide-y divide-current/10">
                        @foreach ($upcomingWeek as $item)
                            <div class="flex items-center justify-between py-2.5 text-sm">
                                <div class="min-w-0">
                                    <p class="font-medium text-current truncate">{{ $item->title }}</p>
                                    <p class="text-xs text-[#637067] dark:text-[#b2beb2]">
                                        <x-datetime :value="$item->start" :household="$household" />
                                    </p>
                                </div>
                                <span class="rounded-full bg-current/10 px-2 py-0.5 text-xs text-current/80">
                                    {{ $item->sourceKey }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>

        {{-- 6. Informasi Keluarga --}}
        <x-card :title="__('Informasi Keluarga')">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 text-sm">
                <div class="rounded-2xl border border-current/10 bg-current/5 p-4">
                    <span class="block text-xs text-[#637067] dark:text-[#b2beb2]">{{ __('Zona Waktu') }}</span>
                    <span class="mt-1 block font-semibold text-base">{{ $household?->timezone ?? 'Asia/Jakarta' }}</span>
                </div>
                <div class="rounded-2xl border border-current/10 bg-current/5 p-4">
                    <span class="block text-xs text-[#637067] dark:text-[#b2beb2]">{{ __('Hari Mulai Periode') }}</span>
                    <span class="mt-1 block font-semibold text-base">{{ __('Tanggal :day', ['day' => $household?->finance_period_start_day ?? 1]) }}</span>
                </div>
                <div class="rounded-2xl border border-current/10 bg-current/5 p-4">
                    <span class="block text-xs text-[#637067] dark:text-[#b2beb2]">{{ __('Kapasitas Anggota') }}</span>
                    <span class="mt-1 block font-semibold text-base">{{ $household?->members?->count() ?? 1 }} / 2</span>
                </div>
            </div>
        </x-card>
    </div>
</x-app-layout>
