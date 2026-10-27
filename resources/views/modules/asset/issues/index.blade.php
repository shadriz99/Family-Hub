<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('assets.index') }}" class="text-xs text-stone-500 hover:text-stone-900 dark:hover:text-white">
                    ← Kembali ke Aset
                </a>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900 dark:text-white">
                Laporan Kerusakan Rumah
            </h1>
            <p class="text-sm text-stone-500 dark:text-stone-400">
                Pipa bocor, stopkontak rusak, genteng retak, atau keluhan peralatan rumah tangga
            </p>
        </div>
        <a href="{{ route('assets.issues.create') }}" class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-emerald-500">
            + Lapor Kerusakan
        </a>
    </div>

    {{-- Sub Navigation Tabs --}}
    <div class="mb-6 flex flex-wrap gap-2 border-b border-stone-200 pb-3 dark:border-stone-800">
        <a href="{{ route('assets.index') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">
            📦 Aset
        </a>
        <a href="{{ route('assets.maintenance') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">
            🔧 Perawatan
        </a>
        <a href="{{ route('assets.documents') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">
            📄 Dokumen & Pajak
        </a>
        <a href="{{ route('assets.issues') }}" class="rounded-lg bg-emerald-50 px-3.5 py-1.5 text-sm font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
            🛠️ Kerusakan ({{ $totalCount }})
        </a>
        <a href="{{ route('assets.vendors') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">
            👨‍🔧 Vendor / Teknisi
        </a>
    </div>

    {{-- Tabs Terbuka vs Selesai --}}
    <div class="mb-6 flex gap-2">
        <button wire:click="$set('tab', 'open')" class="rounded-xl px-4 py-2 text-xs font-semibold {{ $tab === 'open' ? 'bg-stone-900 text-white dark:bg-white dark:text-stone-900' : 'bg-stone-100 text-stone-600 hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300' }}">
            Masalah Terbuka
        </button>
        <button wire:click="$set('tab', 'resolved')" class="rounded-xl px-4 py-2 text-xs font-semibold {{ $tab === 'resolved' ? 'bg-stone-900 text-white dark:bg-white dark:text-stone-900' : 'bg-stone-100 text-stone-600 hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300' }}">
            Sudah Selesai
        </button>
    </div>

    @if($issues->isEmpty())
        <div class="rounded-2xl border border-dashed border-stone-300 p-12 text-center dark:border-stone-800">
            <span class="text-4xl">✨</span>
            <p class="mt-2 text-sm text-stone-500">Tidak ada laporan kerusakan pada daftar ini.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($issues as $issue)
                @php
                    $daysOpen = $issue->created_at ? (int) $issue->created_at->diffInDays(now()) : 0;
                @endphp
                <a href="{{ route('assets.issues.show', $issue->id) }}" class="group flex flex-col justify-between rounded-2xl border border-stone-200 bg-white p-4 shadow-xs transition hover:border-emerald-500 hover:shadow-md dark:border-stone-800 dark:bg-stone-900">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $issue->priority->badgeClass() }}">
                                    {{ $issue->priority->label() }}
                                </span>
                                <span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $issue->status->badgeClass() }}">
                                    {{ $issue->status->label() }}
                                </span>
                            </div>
                            @if($tab === 'open' && $daysOpen > 0)
                                <span class="text-[11px] text-stone-400">
                                    terbuka {{ $daysOpen }} hari
                                </span>
                            @endif
                        </div>

                        <h3 class="mt-2 text-base font-semibold text-stone-900 group-hover:text-emerald-600 dark:text-white dark:group-hover:text-emerald-400">
                            {{ $issue->title }}
                        </h3>

                        <div class="mt-1 text-xs text-stone-500">
                            @if($issue->asset)
                                <span>📦 {{ $issue->asset->name }}</span>
                            @endif
                            @if($issue->location)
                                <span>📍 {{ $issue->location->name }}</span>
                            @endif
                        </div>

                        @if($issue->description)
                            <p class="mt-2 text-xs text-stone-600 dark:text-stone-300 line-clamp-2">
                                {{ $issue->description }}
                            </p>
                        @endif
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-stone-100 pt-3 text-xs text-stone-400 dark:border-stone-800">
                        <span>Oleh {{ $issue->reporter?->name ?? 'Keluarga' }}</span>
                        @if($issue->scheduled_on)
                            <span class="text-cyan-600 dark:text-cyan-400 font-medium">Jadwal: {{ $issue->scheduled_on->translatedFormat('d M') }}</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
