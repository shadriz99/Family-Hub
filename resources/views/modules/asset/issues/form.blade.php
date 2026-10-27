<div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-stone-900 dark:text-white">
                {{ $issueId ? 'Edit Laporan Kerusakan' : 'Lapor Kerusakan / Masalah' }}
            </h1>
            <p class="text-sm text-stone-500 dark:text-stone-400">
                Laporkan kerusakan bagian rumah, instalasi pipa, listrik, atau peralatan keluarga
            </p>
        </div>
        <a href="{{ route('assets.issues') }}" class="text-sm text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white">
            ← Kembali
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-xs dark:border-stone-800 dark:bg-stone-900">
            <h2 class="text-base font-semibold text-stone-900 dark:text-white mb-4">Detail Masalah</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Judul Masalah --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">
                        Judul Masalah / Kerusakan <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" wire:model="title" placeholder="mis. Keran wastafel dapur bocor, AC kamar utama tidak dingin" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" required />
                    @error('title') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                {{-- Tingkat Urgensi / Prioritas --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">
                        Tingkat Urgensi <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="flex items-center gap-2 rounded-xl border border-stone-200 p-2.5 text-xs cursor-pointer hover:bg-stone-50 dark:border-stone-700 dark:hover:bg-stone-800">
                            <input type="radio" wire:model="priority" value="low" class="text-emerald-600 focus:ring-emerald-500" />
                            <span>Rendah</span>
                        </label>
                        <label class="flex items-center gap-2 rounded-xl border border-stone-200 p-2.5 text-xs cursor-pointer hover:bg-stone-50 dark:border-stone-700 dark:hover:bg-stone-800">
                            <input type="radio" wire:model="priority" value="medium" class="text-emerald-600 focus:ring-emerald-500" />
                            <span>Sedang</span>
                        </label>
                        <label class="flex items-center gap-2 rounded-xl border border-rose-300 bg-rose-50/50 p-2.5 text-xs cursor-pointer hover:bg-rose-100/50 dark:border-rose-900/50 dark:bg-rose-950/20">
                            <input type="radio" wire:model="priority" value="urgent" class="text-rose-600 focus:ring-rose-500" />
                            <span class="font-bold text-rose-700 dark:text-rose-400">🚨 Mendesak</span>
                        </label>
                    </div>
                </div>

                {{-- Terkait Aset --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Terkait Aset (Opsional)</label>
                    <select wire:model.live="asset_id" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white">
                        <option value="">Tidak Terkait Aset Tertentu</option>
                        @foreach($assets as $a)
                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Lokasi / Area --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Lokasi / Ruangan</label>
                    <select wire:model="location_id" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white">
                        <option value="">Pilih Lokasi</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Deskripsi Masalah --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Deskripsi Kerusakan / Gejala</label>
                    <textarea wire:model="description" rows="3" placeholder="Jelaskan kondisi kerusakan, air menetes di mana, kapan mulai terjadi..." class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white"></textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('assets.issues') }}" class="rounded-xl border border-stone-200 bg-white px-5 py-2.5 text-sm font-semibold text-stone-700 shadow-xs hover:bg-stone-50 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-300 dark:hover:bg-stone-800">
                Batal
            </a>
            <button type="submit" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-emerald-500">
                Simpan Laporan
            </button>
        </div>
    </form>
</div>
