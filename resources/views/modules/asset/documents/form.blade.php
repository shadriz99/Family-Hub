<div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">
                {{ $documentId ? 'Edit Dokumen' : 'Tambah Dokumen / Garansi' }}
            </h1>
            <p class="text-sm text-[#637067] dark:text-[#637067]">
                Pajak STNK tahunan, plat 5 tahunan, garansi barang, atau asuransi
            </p>
        </div>
        <a href="{{ route('assets.documents') }}" class="text-sm text-[#637067] hover:text-[#263c32] dark:text-[#637067] dark:hover:text-white">
            ← Kembali
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-5 shadow-xs dark:bg-[#1f2c25]">
            <h2 class="text-base font-semibold text-[#263c32] dark:text-[#f3f1e8] mb-4">Detail Dokumen</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Judul Dokumen --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">
                        Judul Dokumen <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" wire:model="title" placeholder="mis. Pajak STNK Tahunan Honda Vario, Garansi Kulkas Sharp" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:bg-[#fffefa] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" required />
                    @error('title') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                {{-- Tipe Dokumen --}}
                <div>
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">
                        Jenis Dokumen <span class="text-rose-500">*</span>
                    </label>
                    <select wire:model="type" class="w-full rounded-xl border border-current/10 bg-current/5 px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]">
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
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Terkait Aset (Opsional)</label>
                    <select wire:model="assetId" class="w-full rounded-xl border border-current/10 bg-current/5 px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]">
                        <option value="">Tidak Terkait Aset Tertentu (Umum)</option>
                        @foreach($assets as $a)
                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Nomor Dokumen & Penerbit --}}
                <div>
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Nomor Dokumen / Polis</label>
                    <input type="text" wire:model="number" placeholder="mis. 12345/STNK/2026" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Instansi / Penerbit</label>
                    <input type="text" wire:model="issuer" placeholder="mis. Samsat Jakarta Selatan, Sharp Indonesia" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                </div>

                {{-- Tanggal Terbit & Kedaluwarsa --}}
                <div>
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Tanggal Terbit</label>
                    <input type="date" wire:model="issued_on" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Berlaku Sampai (Kedaluwarsa)</label>
                    <input type="date" wire:model="expires_on" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                </div>

                {{-- Estimasi Biaya Perpanjangan & Integrasi Finance --}}
                <div>
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Estimasi Biaya Perpanjang (Rp)</label>
                    <input type="number" wire:model="renewal_cost_estimate" placeholder="mis. 350000" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <input type="checkbox" id="add_to_finance_plan" wire:model="add_to_finance_plan" class="h-4 w-4 rounded border-current/15 text-[#28543e] focus:ring-[#28543e] dark:focus:ring-[#bad6ae]" />
                    <label for="add_to_finance_plan" class="text-xs text-[#263c32] dark:text-[#b2beb2] cursor-pointer">
                        <x-heroicon-o-banknotes class="mr-1 inline-block size-4 align-text-bottom" /> Masukkan ke Rencana Pengeluaran (Finance PlannedItem)
                    </label>
                </div>

                {{-- Catatan --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Catatan Tambahan</label>
                    <textarea wire:model="notes" rows="2" placeholder="Catatan tempat menyimpan dokumen fisik, syarat perpanjangan..." class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]"></textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('assets.documents') }}" class="rounded-xl border border-current/10 bg-[#fffefa] px-5 py-2.5 text-sm font-semibold text-[#263c32] shadow-xs hover:bg-current/5 dark:bg-[#1f2c25] dark:text-[#b2beb2]">
                Batal
            </a>
            <button type="submit" class="rounded-xl bg-[#28543e] px-5 py-2.5 text-sm font-semibold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-xs hover:opacity-90">
                Simpan Dokumen
            </button>
        </div>
    </form>
</div>
