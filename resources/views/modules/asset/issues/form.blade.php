<div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">
                {{ $issueId ? 'Edit Laporan Kerusakan' : 'Lapor Kerusakan / Masalah' }}
            </h1>
            <p class="text-sm text-[#637067] dark:text-[#637067]">
                Laporkan kerusakan bagian rumah, instalasi pipa, listrik, atau peralatan keluarga
            </p>
        </div>
        <a href="{{ route('assets.issues') }}" class="text-sm text-[#637067] hover:text-[#263c32] dark:text-[#637067] dark:hover:text-white">
            ← Kembali
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-5 shadow-xs dark:bg-[#1f2c25]">
            <h2 class="text-base font-semibold text-[#263c32] dark:text-[#f3f1e8] mb-4">Detail Masalah</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Judul Masalah --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">
                        Judul Masalah / Kerusakan <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" wire:model="title" placeholder="mis. Keran wastafel dapur bocor, AC kamar utama tidak dingin" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:bg-[#fffefa] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" required />
                    @error('title') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                {{-- Tingkat Urgensi / Prioritas --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">
                        Tingkat Urgensi <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="flex items-center gap-2 rounded-xl border border-current/10 p-2.5 text-xs cursor-pointer hover:bg-current/5">
                            <input type="radio" wire:model="priority" value="low" class="text-[#28543e] focus:ring-[#28543e] dark:focus:ring-[#bad6ae]" />
                            <span>Rendah</span>
                        </label>
                        <label class="flex items-center gap-2 rounded-xl border border-current/10 p-2.5 text-xs cursor-pointer hover:bg-current/5">
                            <input type="radio" wire:model="priority" value="medium" class="text-[#28543e] focus:ring-[#28543e] dark:focus:ring-[#bad6ae]" />
                            <span>Sedang</span>
                        </label>
                        <label class="flex items-center gap-2 rounded-xl border border-rose-300 bg-rose-50/50 p-2.5 text-xs cursor-pointer hover:bg-rose-100/50 dark:border-rose-900/50 dark:bg-rose-950/20">
                            <input type="radio" wire:model="priority" value="urgent" class="text-[#ad3f45] focus:ring-rose-500" />
                            <span class="inline-flex items-center gap-1 font-bold text-[#ad3f45] dark:text-[#ffafb1]"><x-heroicon-o-exclamation-triangle class="size-4" /> Mendesak</span>
                        </label>
                    </div>
                </div>

                {{-- Terkait Aset --}}
                <div>
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Terkait Aset (Opsional)</label>
                    <select wire:model.live="asset_id" class="w-full rounded-xl border border-current/10 bg-current/5 px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]">
                        <option value="">Tidak Terkait Aset Tertentu</option>
                        @foreach($assets as $a)
                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Lokasi / Area --}}
                <div>
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Lokasi / Ruangan</label>
                    <select wire:model="location_id" class="w-full rounded-xl border border-current/10 bg-current/5 px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]">
                        <option value="">Pilih Lokasi</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Deskripsi Masalah --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Deskripsi Kerusakan / Gejala</label>
                    <textarea wire:model="description" rows="3" placeholder="Jelaskan kondisi kerusakan, air menetes di mana, kapan mulai terjadi..." class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:bg-[#fffefa] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]"></textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('assets.issues') }}" class="rounded-xl border border-current/10 bg-[#fffefa] px-5 py-2.5 text-sm font-semibold text-[#263c32] shadow-xs hover:bg-current/5 dark:bg-[#1f2c25] dark:text-[#b2beb2]">
                Batal
            </a>
            <button type="submit" wire:loading.attr="disabled" x-bind:disabled="$store.network?.isOffline" class="rounded-xl bg-[#28543e] px-5 py-2.5 text-sm font-semibold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-xs hover:opacity-90">
                Simpan Laporan
            </button>
        </div>
    </form>
</div>
