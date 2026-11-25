<div class="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">
                {{ $vendorId ? 'Edit Vendor / Teknisi' : 'Tambah Vendor / Teknisi' }}
            </h1>
            <p class="text-sm text-[#637067] dark:text-[#637067]">
                Simpan kontak bengkel atau tukang servis langganan keluarga
            </p>
        </div>
        <a href="{{ route('assets.vendors') }}" class="text-sm text-[#637067] hover:text-[#263c32] dark:text-[#637067] dark:hover:text-white">
            ← Kembali
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-5 shadow-xs dark:bg-[#1f2c25]">
            <h2 class="text-base font-semibold text-[#263c32] dark:text-[#f3f1e8] mb-4">Informasi Vendor</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Nama Vendor --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">
                        Nama Vendor / Teknisi / Bengkel <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" wire:model="name" placeholder="mis. Pak Joko AC, Bengkel AHASS Motor, Sentosa Mandiri Pipa" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:bg-[#fffefa] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" required />
                    @error('name') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>

                {{-- Jenis Layanan (Multi) --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-2">
                        Jenis Layanan (Pilih beberapa)
                    </label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($serviceOptions as $opt)
                            <label class="flex items-center gap-1.5 rounded-lg border border-current/10 px-3 py-1.5 text-xs cursor-pointer hover:bg-current/5">
                                <input type="checkbox" wire:model="services" value="{{ $opt }}" class="h-3.5 w-3.5 rounded border-current/15 text-[#28543e] focus:ring-[#28543e] dark:focus:ring-[#bad6ae]" />
                                <span>{{ $opt }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- WhatsApp & Phone --}}
                <div>
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Nomor WhatsApp</label>
                    <input type="text" wire:model="whatsapp" placeholder="mis. 08123456789" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:bg-[#fffefa] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Telepon Lainnya (Opsional)</label>
                    <input type="text" wire:model="phone" placeholder="mis. 021-123456" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:bg-[#fffefa] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                </div>

                {{-- Rating & Favorit --}}
                <div>
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Rating Kepuasan (1-5)</label>
                    <select wire:model="rating" class="w-full rounded-xl border border-current/10 bg-current/5 px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]">
                        <option value="5">★★★★★ Sangat Puas (5)</option>
                        <option value="4">★★★★☆ Puas (4)</option>
                        <option value="3">★★★☆☆ Cukup (3)</option>
                        <option value="2">★★☆☆☆ Kurang (2)</option>
                        <option value="1">★☆☆☆☆ Kecewa (1)</option>
                    </select>
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <input type="checkbox" id="is_fav" wire:model="is_favorite" class="h-4 w-4 rounded border-current/15 text-[#28543e] focus:ring-[#28543e] dark:focus:ring-[#bad6ae]" />
                    <label for="is_fav" class="text-xs text-[#263c32] dark:text-[#b2beb2] cursor-pointer">
                        ⭐ Tandai sebagai Vendor Favorit / Rekomendasi
                    </label>
                </div>

                {{-- Alamat --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Alamat Bengkel / Toko</label>
                    <input type="text" wire:model="address" placeholder="mis. Jl. Kebon Jeruk No. 12" class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                </div>

                {{-- Catatan --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Catatan Tambahan</label>
                    <textarea wire:model="notes" rows="2" placeholder="Jam buka, spesialisasi merek tertentu, kontak darurat..." class="w-full rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]"></textarea>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('assets.vendors') }}" class="rounded-xl border border-current/10 bg-[#fffefa] px-5 py-2.5 text-sm font-semibold text-[#263c32] shadow-xs hover:bg-current/5 dark:bg-[#1f2c25] dark:text-[#b2beb2]">
                Batal
            </a>
            <button type="submit" wire:loading.attr="disabled" x-bind:disabled="$store.network?.isOffline" class="rounded-xl bg-[#28543e] px-5 py-2.5 text-sm font-semibold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-xs hover:opacity-90">
                Simpan Vendor
            </button>
        </div>
    </form>
</div>
