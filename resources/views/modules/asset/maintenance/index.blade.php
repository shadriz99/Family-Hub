<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('assets.index') }}" class="text-xs text-stone-500 hover:text-stone-900 dark:hover:text-white">
                    ← Kembali ke Aset
                </a>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900 dark:text-white">
                Jadwal Perawatan Aset
            </h1>
            <p class="text-sm text-stone-500 dark:text-stone-400">
                Semua rencana pemeliharaan berkala lintas peralatan dan kendaraan keluarga
            </p>
        </div>
    </div>

    {{-- Sub Navigation Tabs --}}
    <div class="mb-6 flex flex-wrap gap-2 border-b border-stone-200 pb-3 dark:border-stone-800">
        <a href="{{ route('assets.index') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">
            📦 Aset
        </a>
        <a href="{{ route('assets.maintenance') }}" class="rounded-lg bg-emerald-50 px-3.5 py-1.5 text-sm font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
            🔧 Perawatan ({{ $totalCount }})
        </a>
        <a href="{{ route('assets.documents') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">
            📄 Dokumen & Pajak
        </a>
        <a href="{{ route('assets.issues') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">
            🛠️ Kerusakan
        </a>
        <a href="{{ route('assets.vendors') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">
            👨‍🔧 Vendor / Teknisi
        </a>
    </div>

    {{-- Grouped by Status: Terlambat -> Jatuh Tempo -> Segera -> OK --}}
    @php
        $sections = [
            'overdue' => ['title' => 'Terlambat', 'color' => 'text-rose-600 dark:text-rose-400', 'badge' => 'bg-rose-500/15 text-rose-700 dark:text-rose-400'],
            'due' => ['title' => 'Jatuh Tempo Hari Ini', 'color' => 'text-amber-600 dark:text-amber-400', 'badge' => 'bg-amber-500/15 text-amber-700 dark:text-amber-400'],
            'soon' => ['title' => 'Perlu Segera Dilakukan', 'color' => 'text-orange-600 dark:text-orange-400', 'badge' => 'bg-orange-500/15 text-orange-700 dark:text-orange-400'],
            'ok' => ['title' => 'Jadwal Masih Aman', 'color' => 'text-emerald-600 dark:text-emerald-400', 'badge' => 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400'],
        ];
    @endphp

    <div class="space-y-8">
        @foreach($sections as $statusKey => $meta)
            @if(!empty($grouped[$statusKey]))
                <div>
                    <h2 class="mb-3 flex items-center gap-2 text-base font-semibold {{ $meta['color'] }}">
                        <span>{{ $meta['title'] }}</span>
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $meta['badge'] }}">
                            {{ count($grouped[$statusKey]) }}
                        </span>
                    </h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($grouped[$statusKey] as $plan)
                            @php
                                $effectiveDate = $plan->effectiveDueDate();
                            @endphp
                            <div class="flex flex-col justify-between rounded-2xl border border-stone-200 bg-white p-4 shadow-xs dark:border-stone-800 dark:bg-stone-900">
                                <div>
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <span class="text-xs text-stone-500">
                                                {{ $plan->asset?->name }}
                                                @if($plan->asset?->location)
                                                    · {{ $plan->asset->location->name }}
                                                @endif
                                            </span>
                                            <h3 class="text-base font-semibold text-stone-900 dark:text-white mt-0.5">
                                                {{ $plan->name }}
                                            </h3>
                                        </div>
                                    </div>

                                    <div class="mt-3 space-y-1 text-xs text-stone-600 dark:text-stone-300">
                                        @if($effectiveDate)
                                            <div>Jatuh tempo: <strong>{{ $effectiveDate->translatedFormat('d M Y') }}</strong>
                                                @if($plan->snoozed_until)
                                                    <span class="text-amber-600">(Ditunda s/d {{ $plan->snoozed_until->translatedFormat('d M') }})</span>
                                                @endif
                                            </div>
                                        @endif
                                        @if($plan->next_due_meter)
                                            <div>Target meter: <strong>{{ number_format($plan->next_due_meter, 0, ',', '.') }} km</strong>
                                                @if($plan->asset?->current_meter)
                                                    (kini: {{ number_format($plan->asset->current_meter, 0, ',', '.') }} km)
                                                @endif
                                            </div>
                                        @endif
                                        @if($plan->estimated_cost)
                                            <div class="text-stone-500">Estimasi biaya: Rp {{ number_format($plan->estimated_cost, 0, ',', '.') }}</div>
                                        @endif
                                        @if($plan->instructions)
                                            <p class="text-stone-500 italic mt-1">{{ $plan->instructions }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center justify-between border-t border-stone-100 pt-3 dark:border-stone-800">
                                    <button wire:click="openSnooze('{{ $plan->id }}')" class="text-xs font-medium text-stone-500 hover:text-stone-700 dark:hover:text-stone-300">
                                        ⏰ Tunda
                                    </button>
                                    <a href="{{ route('assets.services.create', ['asset' => $plan->asset_id, 'plan' => $plan->id]) }}" class="inline-flex items-center rounded-lg bg-emerald-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-emerald-500">
                                        Tandai Selesai
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    {{-- Snooze Modal --}}
    @if($showSnoozeModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-stone-900">
                <h3 class="text-base font-bold text-stone-900 dark:text-white">Tunda Jadwal Perawatan</h3>
                <p class="mt-1 text-xs text-stone-500">Pilih durasi penundaan jadwal tanpa memundurkan riwayat catatan servis.</p>

                <div class="mt-4">
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Durasi Penundaan</label>
                    <select wire:model="snoozeDays" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white">
                        <option value="3">3 Hari</option>
                        <option value="7">1 Minggu (7 Hari)</option>
                        <option value="14">2 Minggu (14 Hari)</option>
                        <option value="30">1 Bulan (30 Hari)</option>
                    </select>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button wire:click="$set('showSnoozeModal', false)" class="rounded-xl border border-stone-200 px-4 py-2 text-xs font-semibold text-stone-700 hover:bg-stone-50 dark:border-stone-800 dark:text-stone-300">
                        Batal
                    </button>
                    <button wire:click="saveSnooze" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-500">
                        Simpan Penundaan
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
