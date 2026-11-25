<div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">
                {{ $planId ? 'Edit Rencana Perawatan' : 'Tambah Rencana Perawatan' }}
            </h1>
            <p class="text-sm text-[#637067] dark:text-[#637067]">
                Aset: <strong class="text-[#263c32] dark:text-[#f3f1e8]">{{ $asset->name }}</strong>
            </p>
        </div>
        <a href="{{ route('assets.show', ['asset' => $asset->id, 'tab' => 'maintenance']) }}" class="text-sm text-[#637067] hover:text-[#263c32] dark:text-[#637067] dark:hover:text-white">
            ← Kembali
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-5 shadow-xs dark:bg-[#1f2c25]">
            <h2 class="text-base font-semibold text-[#263c32] dark:text-[#f3f1e8] mb-4">Detail Perawatan</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Nama Rencana --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">
                        Nama Perawatan <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" wire:model="name" placeholder="mis. Cuci AC, Ganti Oli Mesin, Kuras Toren" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:bg-[#fffefa] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" required />
                    @error('name') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                {{-- Pemicu (Trigger) --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">
                        Jenis Pemicu Jadwal <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="flex items-center gap-2 rounded-xl border border-current/10 p-2.5 text-xs cursor-pointer hover:bg-current/5">
                            <input type="radio" wire:model.live="trigger" value="time" class="text-[#28543e] focus:ring-[#28543e] dark:focus:ring-[#bad6ae]" />
                            <span>Berdasarkan Waktu</span>
                        </label>
                        <label class="flex items-center gap-2 rounded-xl border border-current/10 p-2.5 text-xs cursor-pointer hover:bg-current/5">
                            <input type="radio" wire:model.live="trigger" value="meter" class="text-[#28543e] focus:ring-[#28543e] dark:focus:ring-[#bad6ae]" />
                            <span>Berdasarkan Meter</span>
                        </label>
                        <label class="flex items-center gap-2 rounded-xl border border-current/10 p-2.5 text-xs cursor-pointer hover:bg-current/5">
                            <input type="radio" wire:model.live="trigger" value="time_or_meter" class="text-[#28543e] focus:ring-[#28543e] dark:focus:ring-[#bad6ae]" />
                            <span>Waktu atau Meter</span>
                        </label>
                    </div>
                </div>

                {{-- Interval Waktu --}}
                @if(in_array($trigger, ['time', 'time_or_meter']))
                    <div>
                        <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Interval Waktu</label>
                        <input type="number" wire:model="interval_value" min="1" placeholder="mis. 3" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:bg-[#fffefa] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Satuan Waktu</label>
                        <select wire:model="interval_unit" class="w-full rounded-xl border border-current/10 bg-current/5 px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:bg-[#fffefa] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]">
                            <option value="day">Hari</option>
                            <option value="week">Minggu</option>
                            <option value="month">Bulan</option>
                            <option value="year">Tahun</option>
                        </select>
                    </div>
                @endif

                {{-- Interval Meter --}}
                @if(in_array($trigger, ['meter', 'time_or_meter']))
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Interval Meter (km/jam)</label>
                        <input type="number" wire:model="meter_interval" min="10" placeholder="mis. 2000 atau 5000" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:bg-[#fffefa] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                    </div>
                @endif

                {{-- Terakhir Dilakukan --}}
                <div>
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Terakhir Dilakukan (Tanggal)</label>
                    <input type="date" wire:model="last_done_on" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:bg-[#fffefa] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Terakhir Dilakukan (Meter)</label>
                    <input type="number" wire:model="last_done_meter" placeholder="mis. 12000" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:bg-[#fffefa] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                </div>

                {{-- Estimasi Biaya & Vendor --}}
                <div>
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Estimasi Biaya (Rp)</label>
                    <input type="number" wire:model="estimated_cost" placeholder="mis. 150000" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:bg-[#fffefa] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Vendor / Teknisi Langganan</label>
                    <select wire:model="vendor_id" class="w-full rounded-xl border border-current/10 bg-current/5 px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:bg-[#fffefa] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]">
                        <option value="">Pilih Vendor (Opsional)</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}">{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Pengingat & Rencana Keuangan --}}
                <div>
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Ingatkan Berapa Hari Sebelumnya?</label>
                    <input type="number" wire:model="remind_days_before" min="0" max="60" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:bg-[#fffefa] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <input type="checkbox" id="add_to_finance_plan" wire:model="add_to_finance_plan" class="h-4 w-4 rounded border-current/15 text-[#28543e] focus:ring-[#28543e] dark:focus:ring-[#bad6ae]" />
                    <label for="add_to_finance_plan" class="text-xs text-[#263c32] dark:text-[#b2beb2] cursor-pointer">
                        <x-heroicon-o-banknotes class="mr-1 inline-block size-4 align-text-bottom" /> Masukkan estimasi biaya ke Rencana Pengeluaran (Finance PlannedItem)
                    </label>
                </div>

                {{-- Instruksi --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Instruksi / Catatan Servis</label>
                    <textarea wire:model="instructions" rows="2" placeholder="mis. Gunakan oli SPX 2 10W-30 0.8 liter, cuci bersih filter udara" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:bg-[#fffefa] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]"></textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3">
            <div>
                @if($planId)
                    <button type="button" wire:confirm="Apakah Anda yakin ingin menghapus rencana perawatan ini?" wire:click="delete" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-[#ad3f45] hover:bg-rose-100 dark:border-rose-900/40 dark:bg-rose-950/40 dark:text-[#ffafb1]">
                        <x-heroicon-o-trash class="mr-1 inline-block size-4 align-text-bottom" /> Hapus Rencana
                    </button>
                @endif
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('assets.show', ['asset' => $asset->id, 'tab' => 'maintenance']) }}" class="rounded-xl border border-current/10 bg-[#fffefa] px-5 py-2.5 text-sm font-semibold text-[#263c32] shadow-xs hover:bg-current/5 dark:bg-[#1f2c25] dark:text-[#b2beb2]">
                    Batal
                </a>
                <button type="submit" wire:loading.attr="disabled" x-bind:disabled="$store.network?.isOffline" class="rounded-xl bg-[#28543e] px-5 py-2.5 text-sm font-semibold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-xs hover:opacity-90">
                    Simpan Rencana
                </button>
            </div>
        </div>
    </form>
</div>
