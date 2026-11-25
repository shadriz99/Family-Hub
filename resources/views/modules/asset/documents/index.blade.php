<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('assets.index') }}" class="text-xs text-[#637067] hover:text-[#263c32] dark:hover:text-white">
                    ← Kembali ke Aset
                </a>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">
                Dokumen, Pajak & Garansi
            </h1>
            <p class="text-sm text-[#637067] dark:text-[#637067]">
                Pajak STNK tahunan, plat 5 tahunan, polis asuransi, dan masa berlaku garansi
            </p>
        </div>
        <a href="{{ route('assets.documents.create') }}" class="inline-flex items-center rounded-xl bg-[#28543e] px-4 py-2 text-sm font-semibold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-xs hover:opacity-90">
            + Tambah Dokumen
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
        <a href="{{ route('assets.documents') }}" class="rounded-lg bg-[#28543e]/10 px-3.5 py-1.5 text-sm font-semibold text-[#28543e] dark:bg-[#bad6ae]/15 dark:text-[#bad6ae]">
            <x-heroicon-o-document-text class="mr-1 inline-block size-4 align-text-bottom" /> Dokumen & Pajak ({{ $totalCount }})
        </a>
        <a href="{{ route('assets.issues') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-exclamation-circle class="mr-1 inline-block size-4 align-text-bottom" /> Kerusakan
        </a>
        <a href="{{ route('assets.vendors') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-user class="mr-1 inline-block size-4 align-text-bottom" /> Vendor / Teknisi
        </a>
    </div>

    {{-- Filter bar --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-xs dark:bg-[#1f2c25]">
        <label class="flex items-center gap-2 text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] cursor-pointer">
            <input type="checkbox" wire:model.live="expiringOnly" class="h-4 w-4 rounded border-current/15 text-[#28543e] focus:ring-[#28543e] dark:focus:ring-[#bad6ae]" />
            <span class="inline-flex items-center gap-1.5"><x-heroicon-o-exclamation-triangle class="size-4 text-[#87571f] dark:text-[#edc48b]" /> Hanya yang kedaluwarsa ≤ 60 hari ke depan</span>
        </label>
        <div>
            <select wire:model.live="type" class="rounded-xl border border-current/10 bg-current/5 px-3 py-1.5 text-xs text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]">
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
        <div class="rounded-2xl border border-dashed border-current/15 p-12 text-center">
            <x-heroicon-o-document-text class="mx-auto size-12 text-[#637067] dark:text-[#b2beb2]" />
            <p class="mt-2 text-sm text-[#637067]">Tidak ada dokumen yang sesuai dengan filter.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($documents as $doc)
                <div class="flex flex-col justify-between rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-xs dark:bg-[#1f2c25]">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-xs text-[#637067]">
                                    {{ $doc->type->label() }}
                                    @if($doc->asset)
                                        · {{ $doc->asset->name }}
                                    @endif
                                </span>
                                <h3 class="text-base font-semibold text-[#263c32] dark:text-[#f3f1e8] mt-0.5">
                                    {{ $doc->title }}
                                </h3>
                                @if($doc->number)
                                    <p class="text-xs font-mono text-[#637067] mt-0.5">No: {{ $doc->number }}</p>
                                @endif
                            </div>
                            <span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $doc->status->value === 'superseded' ? 'bg-current/5 text-[#637067]' : ($doc->isExpired() ? 'bg-rose-100 text-[#ad3f45] dark:bg-rose-950/60 dark:text-[#ffafb1]' : 'bg-[#28543e]/15 text-[#28543e] dark:bg-[#bad6ae]/15 dark:text-[#bad6ae]') }}">
                                {{ $doc->status->label() }}
                            </span>
                        </div>

                        <div class="mt-4 space-y-1 text-xs text-[#637067] dark:text-[#b2beb2]">
                            @if($doc->expires_on)
                                <div>Masa berlaku: <strong>{{ $doc->expires_on->translatedFormat('d F Y') }}</strong>
                                    @if($doc->isExpired())
                                        <span class="text-[#ad3f45]">(Kedaluwarsa)</span>
                                    @endif
                                </div>
                            @endif
                            @if($doc->renewal_cost_estimate)
                                <div class="text-[#637067]">Estimasi perpanjang: Rp {{ number_format($doc->renewal_cost_estimate, 0, ',', '.') }}</div>
                            @endif
                            @if($doc->issuer)
                                <div class="text-[#637067]">Penerbit: {{ $doc->issuer }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 border-t border-current/10 pt-3 flex justify-end">
                        <a href="{{ route('assets.documents.show', $doc->id) }}" class="text-xs font-semibold text-[#28543e] hover:text-[#28543e]">
                            Rincian & Perpanjang →
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
