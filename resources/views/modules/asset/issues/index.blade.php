<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('assets.index') }}" class="text-xs text-[#637067] hover:text-[#263c32] dark:hover:text-white">
                    ← Kembali ke Aset
                </a>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">
                Laporan Kerusakan Rumah
            </h1>
            <p class="text-sm text-[#637067] dark:text-[#637067]">
                Pipa bocor, stopkontak rusak, genteng retak, atau keluhan peralatan rumah tangga
            </p>
        </div>
        <a href="{{ route('assets.issues.create') }}" class="inline-flex items-center rounded-xl bg-[#28543e] px-4 py-2 text-sm font-semibold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-xs hover:opacity-90">
            + Lapor Kerusakan
        </a>
    </div>

    {{-- Sub Navigation Tabs --}}
    <div class="mb-6 flex flex-wrap gap-2 border-b border-current/10 pb-3">
        <a href="{{ route('assets.index') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-cube class="mr-1 inline-block size-4 align-text-bottom" /> Aset
        </a>
        <a href="{{ route('assets.maintenance') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-wrench class="mr-1 inline-block size-4 align-text-bottom" /> Perawatan
        </a>
        <a href="{{ route('assets.documents') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-document-text class="mr-1 inline-block size-4 align-text-bottom" /> Dokumen & Pajak
        </a>
        <a href="{{ route('assets.issues') }}" class="rounded-lg bg-[#28543e]/10 px-3.5 py-1.5 text-sm font-semibold text-[#28543e] dark:bg-[#bad6ae]/15 dark:text-[#bad6ae]">
            <x-heroicon-o-exclamation-circle class="mr-1 inline-block size-4 align-text-bottom" /> Kerusakan ({{ $totalCount }})
        </a>
        <a href="{{ route('assets.vendors') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-user class="mr-1 inline-block size-4 align-text-bottom" /> Vendor / Teknisi
        </a>
    </div>

    {{-- Tabs Terbuka vs Selesai --}}
    <div class="mb-6 flex gap-2">
        <button wire:click="$set('tab', 'open')" class="rounded-xl px-4 py-2 text-xs font-semibold {{ $tab === 'open' ? 'bg-[#28543e] text-white dark:bg-[#fffefa] dark:text-[#263c32]' : 'bg-current/5 text-[#637067] hover:bg-current/10 dark:bg-current/5 dark:text-[#b2beb2]' }}">
            Masalah Terbuka
        </button>
        <button wire:click="$set('tab', 'resolved')" class="rounded-xl px-4 py-2 text-xs font-semibold {{ $tab === 'resolved' ? 'bg-[#28543e] text-white dark:bg-[#fffefa] dark:text-[#263c32]' : 'bg-current/5 text-[#637067] hover:bg-current/10 dark:bg-current/5 dark:text-[#b2beb2]' }}">
            Sudah Selesai
        </button>
    </div>

    @if($issues->isEmpty())
        <div class="rounded-2xl border border-dashed border-current/15 p-12 text-center">
            <x-heroicon-o-sparkles class="mx-auto size-12 text-[#28543e] dark:text-[#bad6ae]" />
            <p class="mt-2 text-sm text-[#637067]">Tidak ada laporan kerusakan pada daftar ini.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($issues as $issue)
                @php
                    $daysOpen = $issue->created_at ? (int) $issue->created_at->diffInDays(now()) : 0;
                @endphp
                <a href="{{ route('assets.issues.show', $issue->id) }}" class="group flex flex-col justify-between rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-xs transition hover:border-[#28543e] dark:hover:border-[#bad6ae] hover:shadow-md dark:bg-[#1f2c25]">
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
                                <span class="text-[11px] text-[#637067]">
                                    terbuka {{ $daysOpen }} hari
                                </span>
                            @endif
                        </div>

                        <h3 class="mt-2 text-base font-semibold text-[#263c32] group-hover:text-[#28543e] dark:text-[#f3f1e8] dark:group-hover:text-[#28543e] dark:text-[#bad6ae]">
                            {{ $issue->title }}
                        </h3>

                        <div class="mt-1 text-xs text-[#637067]">
                            @if($issue->asset)
                                <span class='inline-flex items-center gap-1'><x-heroicon-o-cube class="size-4 text-[#637067] dark:text-[#b2beb2]" /> {{ $issue->asset->name }}</span>
                            @endif
                            @if($issue->location)
                                <span class='inline-flex items-center gap-1'><x-heroicon-o-map-pin class="size-4 text-[#637067] dark:text-[#b2beb2]" /> {{ $issue->location->name }}</span>
                            @endif
                        </div>

                        @if($issue->description)
                            <p class="mt-2 text-xs text-[#637067] dark:text-[#b2beb2] line-clamp-2">
                                {{ $issue->description }}
                            </p>
                        @endif
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-current/10 pt-3 text-xs text-[#637067]">
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
