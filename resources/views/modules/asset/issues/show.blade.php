<div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('assets.issues') }}" class="text-xs text-stone-500 hover:text-stone-900 dark:hover:text-white">
                    ← Kembali ke Kerusakan
                </a>
                <span class="text-stone-400">/</span>
                <span class="text-xs text-stone-600 dark:text-stone-400">Laporan Kerusakan</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900 dark:text-white">
                {{ $issue->title }}
            </h1>
            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                <span class="rounded-md px-2 py-0.5 font-medium {{ $issue->priority->badgeClass() }}">
                    Prioritas {{ $issue->priority->label() }}
                </span>
                <span class="rounded-md px-2 py-0.5 font-medium {{ $issue->status->badgeClass() }}">
                    {{ $issue->status->label() }}
                </span>
                @if($issue->asset)
                    <span>•</span>
                    <a href="{{ route('assets.show', $issue->asset->id) }}" class="text-emerald-600 hover:underline">
                        📦 {{ $issue->asset->name }}
                    </a>
                @endif
                @if($issue->location)
                    <span>•</span>
                    <span>📍 {{ $issue->location->name }}</span>
                @endif
            </div>
        </div>

        {{-- Workflow Action Buttons --}}
        <div class="flex flex-wrap items-center gap-2">
            @if(in_array($issue->status->value, ['open', 'scheduled', 'in_progress']))
                @if($issue->asset)
                    <a href="{{ route('assets.services.create', ['asset' => $issue->asset_id, 'issue' => $issue->id]) }}" class="inline-flex items-center rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-emerald-500">
                        + Catat Servis Selesai
                    </a>
                @endif

                @if($issue->status->value === 'open')
                    <button wire:click="openScheduleModal" class="inline-flex items-center rounded-xl border border-stone-200 bg-white px-3 py-2 text-xs font-semibold text-stone-700 shadow-xs hover:bg-stone-50 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-300 dark:hover:bg-stone-800">
                        📅 Jadwalkan Teknisi
                    </button>
                    <button wire:click="markInProgress" class="inline-flex items-center rounded-xl border border-stone-200 bg-white px-3 py-2 text-xs font-semibold text-stone-700 shadow-xs hover:bg-stone-50 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-300 dark:hover:bg-stone-800">
                        Sedang Dikerjakan
                    </button>
                @endif

                <button wire:click="openResolveModal" class="inline-flex items-center rounded-xl border border-stone-200 bg-white px-3 py-2 text-xs font-semibold text-stone-700 shadow-xs hover:bg-stone-50 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-300 dark:hover:bg-stone-800">
                    ✓ Selesai Sendiri
                </button>

                <button wire:click="markCancelled" wire:confirm="Batalkan laporan kerusakan ini?" class="text-xs text-stone-400 hover:text-stone-600 px-2 py-1">
                    Batalkan
                </button>
            @endif

            <a href="{{ route('assets.issues.edit', $issue->id) }}" class="inline-flex items-center rounded-xl border border-stone-200 bg-white px-3 py-2 text-xs font-semibold text-stone-700 shadow-xs hover:bg-stone-50 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-300 dark:hover:bg-stone-800">
                ✏️ Edit
            </a>
        </div>
    </div>

    {{-- Detail Card --}}
    <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-xs dark:border-stone-800 dark:bg-stone-900">
        <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2 text-xs">
            <div>
                <dt class="text-stone-500 dark:text-stone-400">Dilaporkan Oleh</dt>
                <dd class="font-medium text-stone-900 dark:text-white mt-0.5">{{ $issue->reporter?->name ?? 'Keluarga' }} ({{ $issue->created_at?->translatedFormat('d F Y') }})</dd>
            </div>
            <div>
                <dt class="text-stone-500 dark:text-stone-400">Jadwal Teknisi</dt>
                <dd class="font-medium text-cyan-600 dark:text-cyan-400 mt-0.5">{{ $issue->scheduled_on ? $issue->scheduled_on->translatedFormat('d F Y') : 'Belum dijadwalkan' }}</dd>
            </div>
            @if($issue->vendor)
                <div>
                    <dt class="text-stone-500 dark:text-stone-400">Teknisi / Vendor</dt>
                    <dd class="font-medium text-stone-900 dark:text-white mt-0.5">
                        <a href="{{ route('assets.vendors.show', $issue->vendor->id) }}" class="text-emerald-600 hover:underline">
                            {{ $issue->vendor->name }}
                        </a>
                    </dd>
                </div>
            @endif
            @if($issue->resolved_on)
                <div>
                    <dt class="text-stone-500 dark:text-stone-400">Tanggal Diselesaikan</dt>
                    <dd class="font-medium text-emerald-600 dark:text-emerald-400 mt-0.5">{{ $issue->resolved_on->translatedFormat('d F Y') }}</dd>
                </div>
            @endif
        </dl>

        @if($issue->description)
            <div class="mt-5 border-t border-stone-100 pt-4 dark:border-stone-800 text-xs">
                <span class="text-stone-500 dark:text-stone-400 block mb-1">Deskripsi Kerusakan</span>
                <p class="text-stone-700 dark:text-stone-300 whitespace-pre-line">{{ $issue->description }}</p>
            </div>
        @endif

        @if($issue->resolution_note)
            <div class="mt-4 rounded-xl bg-emerald-50 p-3 text-xs text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                <strong>Catatan Penyelesaian:</strong> {{ $issue->resolution_note }}
            </div>
        @endif
    </div>

    {{-- Schedule Modal --}}
    @if($showScheduleModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-stone-900">
                <h3 class="text-base font-bold text-stone-900 dark:text-white">Jadwalkan Kunjungan Teknisi</h3>
                <p class="mt-1 text-xs text-stone-500">Jadwal akan otomatis muncul di Kalender Terpadu.</p>

                <div class="mt-4 space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Tanggal Kunjungan <span class="text-rose-500">*</span></label>
                        <input type="date" wire:model="scheduledOn" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" required />
                        @error('scheduledOn') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Pilih Vendor / Teknisi</label>
                        <select wire:model="scheduleVendorId" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white">
                            <option value="">Belum Tentukan Vendor</option>
                            @foreach($vendors as $v)
                                <option value="{{ $v->id }}">{{ $v->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button wire:click="$set('showScheduleModal', false)" class="rounded-xl border border-stone-200 px-4 py-2 text-xs font-semibold text-stone-700 hover:bg-stone-50 dark:border-stone-800 dark:text-stone-300">
                        Batal
                    </button>
                    <button wire:click="saveSchedule" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-500">
                        Simpan Jadwal
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Resolve Modal --}}
    @if($showResolveModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-stone-900">
                <h3 class="text-base font-bold text-stone-900 dark:text-white">Tandai Masalah Selesai</h3>
                <p class="mt-1 text-xs text-stone-500">Tandai bahwa kerusakan telah diperbaiki sendiri atau tanpa biaya servis.</p>

                <div class="mt-4">
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Catatan Penyelesaian</label>
                    <input type="text" wire:model="resolutionNote" placeholder="mis. Sudah diganti seal keran sendiri" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button wire:click="$set('showResolveModal', false)" class="rounded-xl border border-stone-200 px-4 py-2 text-xs font-semibold text-stone-700 hover:bg-stone-50 dark:border-stone-800 dark:text-stone-300">
                        Batal
                    </button>
                    <button wire:click="saveResolve" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-500">
                        Selesaikan
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
