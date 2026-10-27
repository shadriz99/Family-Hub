<div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-stone-900 dark:text-white">
                {{ $planId ? 'Edit Rencana Perawatan' : 'Tambah Rencana Perawatan' }}
            </h1>
            <p class="text-sm text-stone-500 dark:text-stone-400">
                Aset: <strong class="text-stone-800 dark:text-stone-200">{{ $asset->name }}</strong>
            </p>
        </div>
        <a href="{{ route('assets.show', ['asset' => $asset->id, 'tab' => 'maintenance']) }}" class="text-sm text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white">
            ← Kembali
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-xs dark:border-stone-800 dark:bg-stone-900">
            <h2 class="text-base font-semibold text-stone-900 dark:text-white mb-4">Detail Perawatan</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Nama Rencana --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">
                        Nama Perawatan <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" wire:model="name" placeholder="mis. Cuci AC, Ganti Oli Mesin, Kuras Toren" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" required />
                    @error('name') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                {{-- Pemicu (Trigger) --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">
                        Jenis Pemicu Jadwal <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="flex items-center gap-2 rounded-xl border border-stone-200 p-2.5 text-xs cursor-pointer hover:bg-stone-50 dark:border-stone-700 dark:hover:bg-stone-800">
                            <input type="radio" wire:model.live="trigger" value="time" class="text-emerald-600 focus:ring-emerald-500" />
                            <span>Berdasarkan Waktu</span>
                        </label>
                        <label class="flex items-center gap-2 rounded-xl border border-stone-200 p-2.5 text-xs cursor-pointer hover:bg-stone-50 dark:border-stone-700 dark:hover:bg-stone-800">
                            <input type="radio" wire:model.live="trigger" value="meter" class="text-emerald-600 focus:ring-emerald-500" />
                            <span>Berdasarkan Meter</span>
                        </label>
                        <label class="flex items-center gap-2 rounded-xl border border-stone-200 p-2.5 text-xs cursor-pointer hover:bg-stone-50 dark:border-stone-700 dark:hover:bg-stone-800">
                            <input type="radio" wire:model.live="trigger" value="time_or_meter" class="text-emerald-600 focus:ring-emerald-500" />
                            <span>Waktu atau Meter</span>
                        </label>
                    </div>
                </div>

                {{-- Interval Waktu --}}
                @if(in_array($trigger, ['time', 'time_or_meter']))
                    <div>
                        <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Interval Waktu</label>
                        <input type="number" wire:model="interval_value" min="1" placeholder="mis. 3" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Satuan Waktu</label>
                        <select wire:model="interval_unit" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white">
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
                        <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Interval Meter (km/jam)</label>
                        <input type="number" wire:model="meter_interval" min="10" placeholder="mis. 2000 atau 5000" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                    </div>
                @endif

                {{-- Terakhir Dilakukan --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Terakhir Dilakukan (Tanggal)</label>
                    <input type="date" wire:model="last_done_on" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Terakhir Dilakukan (Meter)</label>
                    <input type="number" wire:model="last_done_meter" placeholder="mis. 12000" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                </div>

                {{-- Estimasi Biaya & Vendor --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Estimasi Biaya (Rp)</label>
                    <input type="number" wire:model="estimated_cost" placeholder="mis. 150000" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Vendor / Teknisi Langganan</label>
                    <select wire:model="vendor_id" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white">
                        <option value="">Pilih Vendor (Opsional)</option>
                        @foreach($vendors as $v)
                            <option value="{{ $v->id }}">{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Pengingat & Rencana Keuangan --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Ingatkan Berapa Hari Sebelumnya?</label>
                    <input type="number" wire:model="remind_days_before" min="0" max="60" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <input type="checkbox" id="add_to_finance_plan" wire:model="add_to_finance_plan" class="h-4 w-4 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500" />
                    <label for="add_to_finance_plan" class="text-xs text-stone-700 dark:text-stone-300 cursor-pointer">
                        💰 Masukkan estimasi biaya ke Rencana Pengeluaran (Finance PlannedItem)
                    </label>
                </div>

                {{-- Instruksi --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Instruksi / Catatan Servis</label>
                    <textarea wire:model="instructions" rows="2" placeholder="mis. Gunakan oli SPX 2 10W-30 0.8 liter, cuci bersih filter udara" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white"></textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('assets.show', ['asset' => $asset->id, 'tab' => 'maintenance']) }}" class="rounded-xl border border-stone-200 bg-white px-5 py-2.5 text-sm font-semibold text-stone-700 shadow-xs hover:bg-stone-50 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-300 dark:hover:bg-stone-800">
                Batal
            </a>
            <button type="submit" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-emerald-500">
                Simpan Rencana
            </button>
        </div>
    </form>
</div>
