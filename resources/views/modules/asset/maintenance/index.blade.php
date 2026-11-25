<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('assets.index') }}" class="text-xs text-[#637067] hover:text-[#263c32] dark:hover:text-white">
                    ← Kembali ke Aset
                </a>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">
                Jadwal Perawatan Aset
            </h1>
            <p class="text-sm text-[#637067] dark:text-[#637067]">
                Semua rencana pemeliharaan berkala lintas peralatan dan kendaraan keluarga
            </p>
        </div>
    </div>

    {{-- Sub Navigation Tabs --}}
    <div class="mb-6 flex flex-wrap gap-2 border-b border-current/10 pb-3">
        <a href="{{ route('assets.index') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-cube class="mr-1 inline-block size-4 align-text-bottom" /> Aset
        </a>
        <a href="{{ route('assets.maintenance') }}" class="rounded-lg bg-[#28543e]/10 px-3.5 py-1.5 text-sm font-semibold text-[#28543e] dark:bg-[#bad6ae]/15 dark:text-[#bad6ae]">
            <x-heroicon-o-wrench class="mr-1 inline-block size-4 align-text-bottom" /> Perawatan ({{ $totalCount }})
        </a>
        <a href="{{ route('assets.documents') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-document-text class="mr-1 inline-block size-4 align-text-bottom" /> Dokumen & Pajak
        </a>
        <a href="{{ route('assets.issues') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-exclamation-circle class="mr-1 inline-block size-4 align-text-bottom" /> Kerusakan
        </a>
        <a href="{{ route('assets.vendors') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-user class="mr-1 inline-block size-4 align-text-bottom" /> Vendor / Teknisi
        </a>
    </div>

    {{-- Grouped by Status: Terlambat -> Jatuh Tempo -> Segera -> OK --}}
    @php
        $sections = [
            'overdue' => ['title' => 'Terlambat', 'color' => 'text-[#ad3f45] dark:text-[#ffafb1]', 'badge' => 'bg-[#ad3f45]/15 dark:bg-[#ffafb1]/20 text-[#ad3f45] dark:text-[#ffafb1]'],
            'due' => ['title' => 'Jatuh Tempo Hari Ini', 'color' => 'text-[#87571f] dark:text-[#edc48b]', 'badge' => 'bg-[#87571f]/15 dark:bg-[#edc48b]/20 text-[#87571f] dark:text-[#edc48b]'],
            'soon' => ['title' => 'Perlu Segera Dilakukan', 'color' => 'text-orange-600 dark:text-orange-400', 'badge' => 'bg-orange-500/15 text-orange-700 dark:text-orange-400'],
            'ok' => ['title' => 'Jadwal Masih Aman', 'color' => 'text-[#28543e] dark:text-[#bad6ae]', 'badge' => 'bg-[#28543e] dark:bg-[#bad6ae]/15 text-[#28543e] dark:text-[#bad6ae]'],
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
                            <div class="flex flex-col justify-between rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-xs dark:bg-[#1f2c25]">
                                <div>
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <span class="text-xs text-[#637067]">
                                                {{ $plan->asset?->name }}
                                                @if($plan->asset?->location)
                                                    · {{ $plan->asset->location->name }}
                                                @endif
                                            </span>
                                            <h3 class="text-base font-semibold text-[#263c32] dark:text-[#f3f1e8] mt-0.5">
                                                {{ $plan->name }}
                                            </h3>
                                        </div>
                                    </div>

                                    <div class="mt-3 space-y-1 text-xs text-[#637067] dark:text-[#b2beb2]">
                                        @if($effectiveDate)
                                            <div>Jatuh tempo: <strong>{{ $effectiveDate->translatedFormat('d M Y') }}</strong>
                                                @if($plan->snoozed_until)
                                                    <span class="text-[#87571f]">(Ditunda s/d {{ $plan->snoozed_until->translatedFormat('d M') }})</span>
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
                                            <div class="text-[#637067]">Estimasi biaya: Rp {{ number_format($plan->estimated_cost, 0, ',', '.') }}</div>
                                        @endif
                                        @if($plan->instructions)
                                            <p class="text-[#637067] italic mt-1">{{ $plan->instructions }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center justify-between border-t border-current/10 pt-3">
                                    <div class="flex items-center gap-2">
                                        <button wire:click="openSnooze('{{ $plan->id }}')" class="text-xs font-medium text-[#637067] hover:text-[#263c32] dark:hover:text-[#f3f1e8]">
                                            <x-heroicon-o-clock class="mr-1 inline-block size-4 align-text-bottom" /> Tunda
                                        </button>
                                        <a href="{{ route('assets.plans.edit', ['plan' => $plan->id]) }}" class="text-xs font-medium text-[#637067] hover:text-[#263c32] dark:hover:text-[#f3f1e8]">
                                            <x-heroicon-o-pencil class="mr-1 inline-block size-4 align-text-bottom" /> Edit
                                        </a>
                                        <button wire:confirm="Apakah Anda yakin ingin menghapus rencana perawatan '{{ $plan->name }}'?" wire:click="deletePlan('{{ $plan->id }}')" class="text-xs font-medium text-rose-500 hover:text-[#ad3f45] dark:hover:text-rose-400">
                                            <x-heroicon-o-trash class="mr-1 inline-block size-4 align-text-bottom" /> Hapus
                                        </button>
                                    </div>
                                    <a href="{{ route('assets.services.create', ['asset' => $plan->asset_id, 'plan' => $plan->id]) }}" class="inline-flex items-center rounded-lg bg-[#28543e] px-3.5 py-1.5 text-xs font-semibold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-xs hover:opacity-90">
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
            <div class="w-full max-w-md rounded-2xl bg-[#fffefa] p-6 shadow-xl dark:bg-[#1f2c25]">
                <h3 class="text-base font-bold text-[#263c32] dark:text-[#f3f1e8]">Tunda Jadwal Perawatan</h3>
                <p class="mt-1 text-xs text-[#637067]">Pilih durasi penundaan jadwal tanpa memundurkan riwayat catatan servis.</p>

                <div class="mt-4">
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Durasi Penundaan</label>
                    <select wire:model="snoozeDays" class="w-full rounded-xl border border-current/10 bg-current/5 px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]">
                        <option value="3">3 Hari</option>
                        <option value="7">1 Minggu (7 Hari)</option>
                        <option value="14">2 Minggu (14 Hari)</option>
                        <option value="30">1 Bulan (30 Hari)</option>
                    </select>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button wire:click="$set('showSnoozeModal', false)" class="rounded-xl border border-current/10 px-4 py-2 text-xs font-semibold text-[#263c32] hover:bg-current/5 dark:text-[#b2beb2]">
                        Batal
                    </button>
                    <button wire:click="saveSnooze" class="rounded-xl bg-[#28543e] px-4 py-2 text-xs font-semibold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] hover:opacity-90">
                        Simpan Penundaan
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
