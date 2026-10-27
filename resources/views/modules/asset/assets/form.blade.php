<div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-stone-900 dark:text-white">
                {{ $assetId ? 'Edit Aset' : 'Tambah Aset Baru' }}
            </h1>
            <p class="text-sm text-stone-500 dark:text-stone-400">
                Lengkapi informasi barang atau kendaraan rumah tangga Anda
            </p>
        </div>
        <a href="{{ route('assets.index') }}" class="text-sm text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white">
            ← Kembali
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Section 1: Data Utama --}}
        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-xs dark:border-stone-800 dark:bg-stone-900">
            <h2 class="text-base font-semibold text-stone-900 dark:text-white mb-4">Informasi Utama</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Nama Aset --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">
                        Nama Aset / Barang <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" wire:model="name" placeholder="mis. Honda Vario 160, Kulkas Sharp 2 Pintu, AC Kamar Utama" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" required />
                    @error('name') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                {{-- Kategori --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">
                        Kategori <span class="text-rose-500">*</span>
                    </label>
                    <select wire:model.live="category_id" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" required>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Lokasi / Ruangan --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">
                        Lokasi / Ruangan
                    </label>
                    <select wire:model="location_id" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white">
                        <option value="">Pilih Lokasi</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Merek & Model --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Merek</label>
                    <input type="text" wire:model="brand" placeholder="mis. Daikin, Honda, Sharp" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Model / Tipe</label>
                    <input type="text" wire:model="model" placeholder="mis. FTC25NV14, Vario 160 CBS" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                </div>

                {{-- Nomor Seri --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Nomor Seri / No. Rangka</label>
                    <input type="text" wire:model="serial_number" placeholder="mis. SN123456789" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                </div>

                {{-- Kendaraan Fields (jika kategori kendaraan) --}}
                @if($selectedCategory?->is_vehicle)
                    <div>
                        <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Nomor Polisi (Plat)</label>
                        <input type="text" wire:model="plate_number" placeholder="mis. B 1234 XYZ" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm uppercase font-mono text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Odometer Awal (km)</label>
                        <input type="number" wire:model="initial_meter" placeholder="mis. 15000" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                    </div>
                @endif
            </div>
        </div>

        {{-- Section 2: Pembelian & Garansi Cepat --}}
        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-xs dark:border-stone-800 dark:bg-stone-900">
            <h2 class="text-base font-semibold text-stone-900 dark:text-white mb-4">Pembelian & Garansi</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Tanggal Beli</label>
                    <input type="date" wire:model="purchase_date" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Harga Beli (Rp)</label>
                    <input type="number" wire:model="purchase_price" placeholder="mis. 3500000" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Dibeli Di / Toko</label>
                    <input type="text" wire:model="purchased_from" placeholder="mis. Tokopedia, Electronic City" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                </div>

                {{-- Garansi Cepat (AC3) --}}
                @if(!$assetId)
                    <div class="sm:col-span-2 rounded-xl bg-stone-50 p-3.5 border border-stone-200 dark:bg-stone-800/60 dark:border-stone-700">
                        <label class="block text-xs font-semibold text-stone-800 dark:text-stone-200 mb-2">
                            🛡️ Garansi Cepat (Otomatis Buat Dokumen Garansi)
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] text-stone-500 dark:text-stone-400 mb-1">Durasi Garansi (Bulan)</label>
                                <input type="number" wire:model.live="warranty_months" placeholder="mis. 12 atau 24" class="w-full rounded-lg border border-stone-200 bg-white px-3 py-1.5 text-xs text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                            </div>
                            <div>
                                <label class="block text-[11px] text-stone-500 dark:text-stone-400 mb-1">Atau Berlaku Sampai Tanggal</label>
                                <input type="date" wire:model="warranty_until_date" class="w-full rounded-lg border border-stone-200 bg-white px-3 py-1.5 text-xs text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                            </div>
                        </div>
                    </div>

                    {{-- Opsi Catat Pengeluaran ke Keuangan (AC4) --}}
                    @if(count($accountOptions) > 0)
                        <div class="sm:col-span-2 rounded-xl bg-emerald-50/50 p-3.5 border border-emerald-200/60 dark:bg-emerald-950/20 dark:border-emerald-800/40">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model.live="record_expense" class="h-4 w-4 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500" />
                                <span class="text-xs font-semibold text-emerald-900 dark:text-emerald-300">
                                    Catat pembelian ini sebagai pengeluaran di modul Keuangan (Finance)
                                </span>
                            </label>
                            @if($record_expense)
                                <div class="mt-3">
                                    <label class="block text-[11px] font-medium text-emerald-800 dark:text-emerald-400 mb-1">Dari Akun / Dompet</label>
                                    <select wire:model="account_id" class="w-full rounded-lg border border-emerald-300 bg-white px-3 py-1.5 text-xs text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-emerald-700 dark:bg-stone-800 dark:text-white">
                                        <option value="">Pilih Akun Sumber Dana</option>
                                        @foreach($accountOptions as $accId => $accName)
                                            <option value="{{ $accId }}">{{ $accName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    @endif
                @endif
            </div>
        </div>

        {{-- Section 3: Saran Template Perawatan (AC5) --}}
        @if(!$assetId && count($templates) > 0)
            <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-xs dark:border-stone-800 dark:bg-stone-900">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h2 class="text-base font-semibold text-stone-900 dark:text-white">Saran Rencana Perawatan Rutin</h2>
                        <p class="text-xs text-stone-500 dark:text-stone-400">Centang rencana yang ingin otomatis dijadwalkan untuk aset ini</p>
                    </div>
                </div>

                <div class="space-y-2">
                    @foreach($templates as $idx => $tmpl)
                        <label class="flex items-start gap-3 rounded-xl border border-stone-200 p-3 hover:bg-stone-50 cursor-pointer dark:border-stone-700 dark:hover:bg-stone-800">
                            <input type="checkbox" wire:model="selected_templates.{{ $idx }}" class="mt-0.5 h-4 w-4 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500" />
                            <div class="flex-1 text-xs">
                                <div class="font-semibold text-stone-900 dark:text-white">
                                    {{ $tmpl['name'] }}
                                </div>
                                <div class="text-stone-500 dark:text-stone-400 mt-0.5">
                                    Pemicu: {{ $tmpl['trigger'] === 'time' ? "Setiap {$tmpl['interval_value']} bulan" : ($tmpl['trigger'] === 'meter' ? "Setiap " . number_format($tmpl['meter_interval'], 0, ',', '.') . " km" : "Setiap {$tmpl['interval_value']} bulan atau " . number_format($tmpl['meter_interval'], 0, ',', '.') . " km") }}
                                </div>
                                @if(!empty($tmpl['instructions']))
                                    <div class="text-stone-400 text-[11px] mt-0.5 italic">
                                        {{ $tmpl['instructions'] }}
                                    </div>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Section 4: Catatan & Preferensi --}}
        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-xs dark:border-stone-800 dark:bg-stone-900">
            <h2 class="text-base font-semibold text-stone-900 dark:text-white mb-4">Catatan Tambahan</h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Catatan</label>
                    <textarea wire:model="notes" rows="3" placeholder="Informasi tambahan, toko servis langganan, petunjuk pemakaian..." class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white"></textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" id="is_favorite" wire:model="is_favorite" class="h-4 w-4 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500" />
                    <label for="is_favorite" class="text-xs text-stone-700 dark:text-stone-300 cursor-pointer">
                        ⭐ Tandai sebagai favorit (tampil di FAB jalan pintas pembaruan odometer)
                    </label>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('assets.index') }}" class="rounded-xl border border-stone-200 bg-white px-5 py-2.5 text-sm font-semibold text-stone-700 shadow-xs hover:bg-stone-50 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-300 dark:hover:bg-stone-800">
                Batal
            </a>
            <button type="submit" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-emerald-500">
                {{ $assetId ? 'Simpan Perubahan' : 'Simpan Aset' }}
            </button>
        </div>
    </form>
</div>
