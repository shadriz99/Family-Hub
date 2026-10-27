<div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-stone-900 dark:text-white">
                {{ $documentId ? 'Edit Dokumen' : 'Tambah Dokumen / Garansi' }}
            </h1>
            <p class="text-sm text-stone-500 dark:text-stone-400">
                Pajak STNK tahunan, plat 5 tahunan, garansi barang, atau asuransi
            </p>
        </div>
        <a href="{{ route('assets.documents') }}" class="text-sm text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white">
            ← Kembali
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-xs dark:border-stone-800 dark:bg-stone-900">
            <h2 class="text-base font-semibold text-stone-900 dark:text-white mb-4">Detail Dokumen</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Judul Dokumen --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">
                        Judul Dokumen <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" wire:model="title" placeholder="mis. Pajak STNK Tahunan Honda Vario, Garansi Kulkas Sharp" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" required />
                    @error('title') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                {{-- Tipe Dokumen --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">
                        Jenis Dokumen <span class="text-rose-500">*</span>
                    </label>
                    <select wire:model="type" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white">
                        <option value="warranty">Garansi</option>
                        <option value="vehicle_tax_annual">Pajak STNK Tahunan</option>
                        <option value="vehicle_registration_5y">STNK / Plat 5 Tahunan</option>
                        <option value="insurance">Asuransi</option>
                        <option value="inspection">Uji KIR / Pemeriksaan</option>
                        <option value="certificate">Sertifikat</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>

                {{-- Terkait Aset --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Terkait Aset (Opsional)</label>
                    <select wire:model="assetId" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white">
                        <option value="">Tidak Terkait Aset Tertentu (Umum)</option>
                        @foreach($assets as $a)
                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Nomor Dokumen & Penerbit --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Nomor Dokumen / Polis</label>
                    <input type="text" wire:model="number" placeholder="mis. 12345/STNK/2026" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Instansi / Penerbit</label>
                    <input type="text" wire:model="issuer" placeholder="mis. Samsat Jakarta Selatan, Sharp Indonesia" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                </div>

                {{-- Tanggal Terbit & Kedaluwarsa --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Tanggal Terbit</label>
                    <input type="date" wire:model="issued_on" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Berlaku Sampai (Kedaluwarsa)</label>
                    <input type="date" wire:model="expires_on" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                </div>

                {{-- Estimasi Biaya Perpanjangan & Integrasi Finance --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Estimasi Biaya Perpanjang (Rp)</label>
                    <input type="number" wire:model="renewal_cost_estimate" placeholder="mis. 350000" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <input type="checkbox" id="add_to_finance_plan" wire:model="add_to_finance_plan" class="h-4 w-4 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500" />
                    <label for="add_to_finance_plan" class="text-xs text-stone-700 dark:text-stone-300 cursor-pointer">
                        💰 Masukkan ke Rencana Pengeluaran (Finance PlannedItem)
                    </label>
                </div>

                {{-- Catatan --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Catatan Tambahan</label>
                    <textarea wire:model="notes" rows="2" placeholder="Catatan tempat menyimpan dokumen fisik, syarat perpanjangan..." class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white"></textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('assets.documents') }}" class="rounded-xl border border-stone-200 bg-white px-5 py-2.5 text-sm font-semibold text-stone-700 shadow-xs hover:bg-stone-50 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-300 dark:hover:bg-stone-800">
                Batal
            </a>
            <button type="submit" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-emerald-500">
                Simpan Dokumen
            </button>
        </div>
    </form>
</div>
