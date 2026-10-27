<div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-stone-900 dark:text-white">
                Catat Servis / Perbaikan
            </h1>
            <p class="text-sm text-stone-500 dark:text-stone-400">
                Aset: <strong class="text-stone-800 dark:text-stone-200">{{ $asset->name }}</strong>
            </p>
        </div>
        <a href="{{ route('assets.show', ['asset' => $asset->id, 'tab' => 'history']) }}" class="text-sm text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white">
            ← Kembali
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-xs dark:border-stone-800 dark:bg-stone-900">
            <h2 class="text-base font-semibold text-stone-900 dark:text-white mb-4">Informasi Servis</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Jenis Servis --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Jenis Pekerjaan</label>
                    <select wire:model="type" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white">
                        <option value="preventive">Perawatan Rutin</option>
                        <option value="repair">Perbaikan / Kerusakan</option>
                        <option value="inspection">Pemeriksaan / Cek Rutin</option>
                    </select>
                </div>

                {{-- Tanggal Pelaksanaan --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Tanggal Dilakukan <span class="text-rose-500">*</span></label>
                    <input type="date" wire:model="performed_on" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" required />
                    @error('performed_on') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                {{-- Pembacaan Odometer / Meter --}}
                @if($asset->meter_unit)
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Pembacaan Meter saat Servis ({{ $asset->meter_unit?->value ?? 'km' }})</label>
                        <input type="number" wire:model="meter_reading" placeholder="mis. 15200" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                    </div>
                @endif

                {{-- Deskripsi Pekerjaan --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Deskripsi Pekerjaan</label>
                    <input type="text" wire:model="description" placeholder="mis. Ganti oli mesin MPX 2 dan kuras CVT" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                </div>

                {{-- Suku Cadang & Bahan --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Suku Cadang / Sparepart yang Diganti</label>
                    <input type="text" wire:model="parts" placeholder="mis. Oli SPX 0.8L, Roller CVT, V-belt" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                </div>

                {{-- Vendor / Bengkel --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Vendor / Bengkel / Teknisi</label>
                    <select wire:model="vendor_id" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white">
                        <option value="">Pilih Vendor (Opsional)</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}">{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Total Biaya --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Total Biaya (Rp)</label>
                    <input type="number" wire:model="cost" placeholder="0" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                </div>

                {{-- Garansi Servis (Opsional) --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Garansi Hasil Servis Sampai (Opsional)</label>
                    <input type="date" wire:model="service_warranty_until" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                    <p class="text-[11px] text-stone-500 mt-1">Jika diisi, otomatis membuat dokumen garansi servis pada aset.</p>
                </div>
            </div>
        </div>

        {{-- Section Finance Integration --}}
        @if(count($accountOptions) > 0)
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-5 shadow-xs dark:border-emerald-900/40 dark:bg-emerald-950/20">
                <div class="flex items-center gap-2 mb-3">
                    <input type="checkbox" id="record_expense" wire:model.live="record_expense" class="h-4 w-4 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500" />
                    <label for="record_expense" class="text-sm font-semibold text-emerald-900 dark:text-emerald-300 cursor-pointer">
                        💰 Catat biaya servis ini ke transaksi pengeluaran Keuangan (Finance)
                    </label>
                </div>

                @if($record_expense)
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 pt-2">
                        <div>
                            <label class="block text-xs font-semibold text-emerald-900 dark:text-emerald-300 mb-1">Dari Akun / Dompet</label>
                            <select wire:model="account_id" class="w-full rounded-xl border border-emerald-300 bg-white px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-emerald-800 dark:bg-stone-800 dark:text-white">
                                <option value="">Pilih Akun Sumber Dana</option>
                                @foreach($accountOptions as $accId => $accName)
                                    <option value="{{ $accId }}">{{ $accName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-emerald-900 dark:text-emerald-300 mb-1">Kategori Pengeluaran</label>
                            <select wire:model="category_id" class="w-full rounded-xl border border-emerald-300 bg-white px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-emerald-800 dark:bg-stone-800 dark:text-white">
                                <option value="">Default (Perawatan Rumah / Kendaraan)</option>
                                @foreach($categoryOptions as $cId => $cName)
                                    <option value="{{ $cId }}">{{ $cName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('assets.show', ['asset' => $asset->id, 'tab' => 'history']) }}" class="rounded-xl border border-stone-200 bg-white px-5 py-2.5 text-sm font-semibold text-stone-700 shadow-xs hover:bg-stone-50 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-300 dark:hover:bg-stone-800">
                Batal
            </a>
            <button type="submit" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-emerald-500">
                Simpan Catatan Servis
            </button>
        </div>
    </form>
</div>
