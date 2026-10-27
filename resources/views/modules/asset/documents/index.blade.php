<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('assets.index') }}" class="text-xs text-stone-500 hover:text-stone-900 dark:hover:text-white">
                    ← Kembali ke Aset
                </a>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900 dark:text-white">
                Dokumen, Pajak & Garansi
            </h1>
            <p class="text-sm text-stone-500 dark:text-stone-400">
                Pajak STNK tahunan, plat 5 tahunan, polis asuransi, dan masa berlaku garansi
            </p>
        </div>
        <a href="{{ route('assets.documents.create') }}" class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-emerald-500">
            + Tambah Dokumen
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
        <a href="{{ route('assets.documents') }}" class="rounded-lg bg-emerald-50 px-3.5 py-1.5 text-sm font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
            📄 Dokumen & Pajak ({{ $totalCount }})
        </a>
        <a href="{{ route('assets.issues') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">
            🛠️ Kerusakan
        </a>
        <a href="{{ route('assets.vendors') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">
            👨‍🔧 Vendor / Teknisi
        </a>
    </div>

    {{-- Filter bar --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-xs dark:border-stone-800 dark:bg-stone-900">
        <label class="flex items-center gap-2 text-xs font-semibold text-stone-700 dark:text-stone-300 cursor-pointer">
            <input type="checkbox" wire:model.live="expiringOnly" class="h-4 w-4 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500" />
            <span>⚠️ Hanya yang kedaluwarsa ≤ 60 hari ke depan</span>
        </label>
        <div>
            <select wire:model.live="type" class="rounded-xl border border-stone-200 bg-stone-50 px-3 py-1.5 text-xs text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white">
                <option value="">Semua Jenis Dokumen</option>
                <option value="warranty">Garansi</option>
                <option value="vehicle_tax_annual">Pajak STNK Tahunan</option>
                <option value="vehicle_registration_5y">STNK / Plat 5 Tahunan</option>
                <option value="insurance">Asuransi</option>
                <option value="inspection">Uji KIR / Pemeriksaan</option>
                <option value="certificate">Sertifikat</option>
                <option value="other">Lainnya</option>
            </select>
        </div>
    </div>

    @if($documents->isEmpty())
        <div class="rounded-2xl border border-dashed border-stone-300 p-12 text-center dark:border-stone-800">
            <span class="text-4xl">📄</span>
            <p class="mt-2 text-sm text-stone-500">Tidak ada dokumen yang sesuai dengan filter.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($documents as $doc)
                <div class="flex flex-col justify-between rounded-2xl border border-stone-200 bg-white p-4 shadow-xs dark:border-stone-800 dark:bg-stone-900">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-xs text-stone-500">
                                    {{ $doc->type->label() }}
                                    @if($doc->asset)
                                        · {{ $doc->asset->name }}
                                    @endif
                                </span>
                                <h3 class="text-base font-semibold text-stone-900 dark:text-white mt-0.5">
                                    {{ $doc->title }}
                                </h3>
                                @if($doc->number)
                                    <p class="text-xs font-mono text-stone-500 mt-0.5">No: {{ $doc->number }}</p>
                                @endif
                            </div>
                            <span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $doc->status->value === 'superseded' ? 'bg-stone-100 text-stone-500' : ($doc->isExpired() ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400') }}">
                                {{ $doc->status->label() }}
                            </span>
                        </div>

                        <div class="mt-4 space-y-1 text-xs text-stone-600 dark:text-stone-300">
                            @if($doc->expires_on)
                                <div>Masa berlaku: <strong>{{ $doc->expires_on->translatedFormat('d F Y') }}</strong>
                                    @if($doc->isExpired())
                                        <span class="text-rose-600">(Kedaluwarsa)</span>
                                    @endif
                                </div>
                            @endif
                            @if($doc->renewal_cost_estimate)
                                <div class="text-stone-500">Estimasi perpanjang: Rp {{ number_format($doc->renewal_cost_estimate, 0, ',', '.') }}</div>
                            @endif
                            @if($doc->issuer)
                                <div class="text-stone-400">Penerbit: {{ $doc->issuer }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 border-t border-stone-100 pt-3 dark:border-stone-800 flex justify-end">
                        <a href="{{ route('assets.documents.show', $doc->id) }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">
                            Rincian & Perpanjang →
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
