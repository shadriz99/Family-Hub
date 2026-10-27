<div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-stone-900 dark:text-white">
                {{ $vendorId ? 'Edit Vendor / Teknisi' : 'Tambah Vendor / Teknisi' }}
            </h1>
            <p class="text-sm text-stone-500 dark:text-stone-400">
                Simpan kontak bengkel atau tukang servis langganan keluarga
            </p>
        </div>
        <a href="{{ route('assets.vendors') }}" class="text-sm text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white">
            ← Kembali
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-xs dark:border-stone-800 dark:bg-stone-900">
            <h2 class="text-base font-semibold text-stone-900 dark:text-white mb-4">Informasi Vendor</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Nama Vendor --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">
                        Nama Vendor / Teknisi / Bengkel <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" wire:model="name" placeholder="mis. Pak Joko AC, Bengkel AHASS Motor, Sentosa Mandiri Pipa" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" required />
                    @error('name') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                {{-- Jenis Layanan (Multi) --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-2">
                        Jenis Layanan (Pilih beberapa)
                    </label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($serviceOptions as $opt)
                            <label class="flex items-center gap-1.5 rounded-lg border border-stone-200 px-3 py-1.5 text-xs cursor-pointer hover:bg-stone-50 dark:border-stone-700 dark:hover:bg-stone-800">
                                <input type="checkbox" wire:model="services" value="{{ $opt }}" class="h-3.5 w-3.5 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500" />
                                <span>{{ $opt }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- WhatsApp & Phone --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Nomor WhatsApp</label>
                    <input type="text" wire:model="whatsapp" placeholder="mis. 08123456789" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Telepon Lainnya (Opsional)</label>
                    <input type="text" wire:model="phone" placeholder="mis. 021-123456" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                </div>

                {{-- Rating & Favorit --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Rating Kepuasan (1-5)</label>
                    <select wire:model="rating" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white">
                        <option value="5">★★★★★ Sangat Puas (5)</option>
                        <option value="4">★★★★☆ Puas (4)</option>
                        <option value="3">★★★☆☆ Cukup (3)</option>
                        <option value="2">★★☆☆☆ Kurang (2)</option>
                        <option value="1">★☆☆☆☆ Kecewa (1)</option>
                    </select>
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <input type="checkbox" id="is_fav" wire:model="is_favorite" class="h-4 w-4 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500" />
                    <label for="is_fav" class="text-xs text-stone-700 dark:text-stone-300 cursor-pointer">
                        ⭐ Tandai sebagai Vendor Favorit / Rekomendasi
                    </label>
                </div>

                {{-- Alamat --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Alamat Bengkel / Toko</label>
                    <input type="text" wire:model="address" placeholder="mis. Jl. Kebon Jeruk No. 12" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                </div>

                {{-- Catatan --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Catatan Tambahan</label>
                    <textarea wire:model="notes" rows="2" placeholder="Jam buka, spesialisasi merek tertentu, kontak darurat..." class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white"></textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('assets.vendors') }}" class="rounded-xl border border-stone-200 bg-white px-5 py-2.5 text-sm font-semibold text-stone-700 shadow-xs hover:bg-stone-50 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-300 dark:hover:bg-stone-800">
                Batal
            </a>
            <button type="submit" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-emerald-500">
                Simpan Vendor
            </button>
        </div>
    </form>
</div>
