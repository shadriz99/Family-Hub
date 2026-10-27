<div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('assets.vendors') }}" class="text-xs text-stone-500 hover:text-stone-900 dark:hover:text-white">
                    ← Kembali ke Vendor
                </a>
            </div>
            <div class="mt-1 flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight text-stone-900 dark:text-white">
                    {{ $vendor->name }}
                </h1>
                @if($vendor->is_favorite)
                    <span class="text-lg" title="Favorit">⭐</span>
                @endif
            </div>

            @if($vendor->rating)
                <div class="mt-1 text-sm text-amber-500">
                    {{ str_repeat('★', $vendor->rating) }}{{ str_repeat('☆', 5 - $vendor->rating) }}
                </div>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('assets.vendors.edit', $vendor->id) }}" class="inline-flex items-center rounded-xl border border-stone-200 bg-white px-3.5 py-2 text-xs font-semibold text-stone-700 shadow-xs hover:bg-stone-50 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-300 dark:hover:bg-stone-800">
                ✏️ Edit
            </a>
        </div>
    </div>

    {{-- Contact & WhatsApp Hub (US-AST-08 AC2) --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3 mb-6">
        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-xs dark:border-stone-800 dark:bg-stone-900 sm:col-span-2">
            <h2 class="text-sm font-semibold text-stone-900 dark:text-white mb-3">Hubungi Teknisi via WhatsApp</h2>
            <p class="text-xs text-stone-500 mb-3">Pilih aset keluarga untuk otomatis membuat draft pesan reservasi servis:</p>

            <div class="space-y-3">
                <div>
                    <label class="block text-[11px] text-stone-500 mb-1">Aset yang Ingin Diservis (Opsional)</label>
                    <select wire:model.live="selectedAssetId" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-xs text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white">
                        <option value="">Servis Umum (Tanpa Spesifik Aset)</option>
                        @foreach($assets as $a)
                            <option value="{{ $a->id }}">{{ $a->name }} ({{ $a->location?->name ?? 'Rumah' }})</option>
                        @endforeach
                    </select>
                </div>

                @if($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-semibold text-white shadow-xs hover:bg-emerald-500">
                        <span>💬 Buka WhatsApp & Kirim Pesan Reservasi</span>
                    </a>
                @else
                    <p class="text-xs text-stone-400">Nomor WhatsApp belum dicatat untuk vendor ini.</p>
                @endif
            </div>

            <div class="mt-4 pt-3 border-t border-stone-100 text-xs text-stone-500 space-y-1">
                @if($vendor->phone)
                    <div>Telepon: {{ $vendor->phone }}</div>
                @endif
                @if($vendor->address)
                    <div>Alamat: {{ $vendor->address }}</div>
                @endif
                @if($vendor->notes)
                    <div class="mt-2 text-stone-700 dark:text-stone-300 italic">{{ $vendor->notes }}</div>
                @endif
            </div>
        </div>

        {{-- Total Transaksi ke Vendor ini (AC3) --}}
        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-xs dark:border-stone-800 dark:bg-stone-900 flex flex-col justify-between">
            <div>
                <h2 class="text-sm font-semibold text-stone-900 dark:text-white mb-2">Riwayat Pembayaran</h2>
                <span class="text-xs text-stone-500 block">Total Pengeluaran ke Vendor ini:</span>
                <span class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1 block">
                    Rp {{ number_format($totalSpent, 0, ',', '.') }}
                </span>
            </div>
            <div class="mt-4 text-xs text-stone-500 border-t border-stone-100 pt-3">
                Total Servis: <strong>{{ $vendor->serviceRecords->count() }} kali</strong>
            </div>
        </div>
    </div>

    {{-- Riwayat Servis bersama Vendor ini --}}
    <div class="space-y-4">
        <h2 class="text-base font-semibold text-stone-900 dark:text-white">Riwayat Pekerjaan Servis</h2>

        @if($vendor->serviceRecords->isEmpty())
            <div class="rounded-2xl border border-dashed border-stone-300 p-8 text-center dark:border-stone-800">
                <p class="text-sm text-stone-500">Belum ada catatan servis yang ditugaskan ke vendor ini.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($vendor->serviceRecords->sortByDesc('performed_on') as $rec)
                    <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-xs dark:border-stone-800 dark:bg-stone-900">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-xs text-stone-500">
                                    {{ $rec->performed_on->translatedFormat('d F Y') }} • {{ $rec->asset?->name }}
                                </span>
                                <h3 class="text-sm font-semibold text-stone-900 dark:text-white mt-0.5">
                                    {{ $rec->description ?: $rec->type->label() }}
                                </h3>
                                @if($rec->parts)
                                    <p class="text-xs text-stone-500 mt-0.5">Suku cadang: {{ $rec->parts }}</p>
                                @endif
                            </div>
                            <span class="text-sm font-bold text-stone-900 dark:text-white">
                                Rp {{ number_format($rec->cost, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
