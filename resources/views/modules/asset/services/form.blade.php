<div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">
                Catat Servis / Perbaikan
            </h1>
            <p class="text-sm text-[#637067] dark:text-[#637067]">
                Aset: <strong class="text-[#263c32] dark:text-[#f3f1e8]">{{ $asset->name }}</strong>
            </p>
        </div>
        <a href="{{ route('assets.show', ['asset' => $asset->id, 'tab' => 'history']) }}" class="text-sm text-[#637067] hover:text-[#263c32] dark:text-[#637067] dark:hover:text-white">
            ← Kembali
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-5 shadow-xs dark:bg-[#1f2c25]">
            <h2 class="text-base font-semibold text-[#263c32] dark:text-[#f3f1e8] mb-4">Informasi Servis</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Jenis Servis --}}
                <div>
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Jenis Pekerjaan</label>
                    <select wire:model="type" class="w-full rounded-xl border border-current/10 bg-current/5 px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]">
                        <option value="preventive">Perawatan Rutin</option>
                        <option value="repair">Perbaikan / Kerusakan</option>
                        <option value="inspection">Pemeriksaan / Cek Rutin</option>
                    </select>
                </div>

                {{-- Tanggal Pelaksanaan --}}
                <div>
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Tanggal Dilakukan <span class="text-rose-500">*</span></label>
                    <input type="date" wire:model="performed_on" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" required />
                    @error('performed_on') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                {{-- Pembacaan Odometer / Meter --}}
                @if($asset->meter_unit)
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Pembacaan Meter saat Servis ({{ $asset->meter_unit?->value ?? 'km' }})</label>
                        <input type="number" wire:model="meter_reading" placeholder="mis. 15200" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                    </div>
                @endif

                {{-- Deskripsi Pekerjaan --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Deskripsi Pekerjaan</label>
                    <input type="text" wire:model="description" placeholder="mis. Ganti oli mesin MPX 2 dan kuras CVT" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                </div>

                {{-- Suku Cadang & Bahan --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Suku Cadang / Sparepart yang Diganti</label>
                    <input type="text" wire:model="parts" placeholder="mis. Oli SPX 0.8L, Roller CVT, V-belt" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                </div>

                {{-- Vendor / Bengkel --}}
                <div>
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Vendor / Bengkel / Teknisi</label>
                    <select wire:model="vendor_id" class="w-full rounded-xl border border-current/10 bg-current/5 px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]">
                        <option value="">Pilih Vendor (Opsional)</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}">{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Total Biaya --}}
                <div>
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Total Biaya (Rp)</label>
                    <input type="number" wire:model="cost" placeholder="0" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                </div>

                {{-- Garansi Servis (Opsional) --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Garansi Hasil Servis Sampai (Opsional)</label>
                    <input type="date" wire:model="service_warranty_until" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                    <p class="text-[11px] text-[#637067] mt-1">Jika diisi, otomatis membuat dokumen garansi servis pada aset.</p>
                </div>
            </div>
        </div>

        {{-- Section Finance Integration --}}
        @if(count($accountOptions) > 0)
            <div class="rounded-2xl border border-[#28543e]/20 bg-[#28543e]/5 p-5 shadow-xs dark:border-[#bad6ae]/25 dark:bg-[#bad6ae]/10">
                <div class="flex items-center gap-2 mb-3">
                    <input type="checkbox" id="record_expense" wire:model.live="record_expense" class="h-4 w-4 rounded border-current/15 text-[#28543e] focus:ring-[#28543e] dark:focus:ring-[#bad6ae]" />
                    <label for="record_expense" class="text-sm font-semibold text-[#28543e] dark:text-[#bad6ae] cursor-pointer">
                        <x-heroicon-o-banknotes class="mr-1 inline-block size-4 align-text-bottom" /> Catat biaya servis ini ke transaksi pengeluaran Keuangan (Finance)
                    </label>
                </div>

                @if($record_expense)
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 pt-2">
                        <div>
                            <label class="block text-xs font-semibold text-[#28543e] dark:text-[#bad6ae] mb-1">Dari Akun / Dompet</label>
                            <select wire:model="account_id" class="w-full rounded-xl border border-[#28543e]/30 bg-[#fffefa] px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:border-emerald-800 dark:bg-current/5 dark:text-[#f3f1e8]">
                                <option value="">Pilih Akun Sumber Dana</option>
                                @foreach($accountOptions as $accId => $accName)
                                    <option value="{{ $accId }}">{{ $accName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#28543e] dark:text-[#bad6ae] mb-1">Kategori Pengeluaran</label>
                            <select wire:model="category_id" class="w-full rounded-xl border border-[#28543e]/30 bg-[#fffefa] px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:border-emerald-800 dark:bg-current/5 dark:text-[#f3f1e8]">
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
            <a href="{{ route('assets.show', ['asset' => $asset->id, 'tab' => 'history']) }}" class="rounded-xl border border-current/10 bg-[#fffefa] px-5 py-2.5 text-sm font-semibold text-[#263c32] shadow-xs hover:bg-current/5 dark:bg-[#1f2c25] dark:text-[#b2beb2]">
                Batal
            </a>
            <button type="submit" wire:loading.attr="disabled" x-bind:disabled="$store.network?.isOffline" class="rounded-xl bg-[#28543e] px-5 py-2.5 text-sm font-semibold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-xs hover:opacity-90">
                Simpan Catatan Servis
            </button>
        </div>
    </form>
</div>
