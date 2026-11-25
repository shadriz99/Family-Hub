<div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('assets.index') }}" class="text-xs text-[#637067] hover:text-[#263c32] dark:hover:text-white">
                    ← Kembali ke Aset
                </a>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">
                Daftar Lokasi & Ruangan Rumah
            </h1>
            <p class="text-sm text-[#637067] dark:text-[#637067]">
                Kelola area atau ruangan untuk mengelompokkan peralatan dan aset keluarga
            </p>
        </div>
    </div>

    {{-- Sub Navigation Tabs --}}
    <div class="mb-6 flex flex-wrap gap-2 border-b border-current/10 pb-3">
        <a href="{{ route('assets.index') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-cube class="mr-1 inline-block size-4 align-text-bottom" /> Aset
        </a>
        <a href="{{ route('assets.maintenance') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-wrench class="mr-1 inline-block size-4 align-text-bottom" /> Perawatan
        </a>
        <a href="{{ route('assets.documents') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-document-text class="mr-1 inline-block size-4 align-text-bottom" /> Dokumen & Pajak
        </a>
        <a href="{{ route('assets.issues') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-exclamation-circle class="mr-1 inline-block size-4 align-text-bottom" /> Kerusakan
        </a>
        <a href="{{ route('assets.vendors') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-[#637067] hover:bg-current/5 dark:text-[#637067]">
            <x-heroicon-o-user class="mr-1 inline-block size-4 align-text-bottom" /> Vendor / Teknisi
        </a>
        <a href="{{ route('assets.locations') }}" class="rounded-lg bg-[#28543e]/10 px-3.5 py-1.5 text-sm font-semibold text-[#28543e] dark:bg-[#bad6ae]/15 dark:text-[#bad6ae]">
            <x-heroicon-o-map-pin class="mr-1 inline-block size-4 align-text-bottom" /> Lokasi ({{ $locations->count() }})
        </a>
    </div>

    {{-- Form Tambah Lokasi Cepat --}}
    <div class="mb-6 rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-xs dark:bg-[#1f2c25]">
        <h2 class="text-xs font-semibold text-[#263c32] dark:text-[#f3f1e8] mb-2">Tambah Ruangan / Lokasi Baru</h2>
        <form wire:submit="createLocation" class="flex gap-2">
            <input type="text" wire:model="newName" placeholder="Nama ruangan (mis. Balkon, Gudang Belakang, Rooftop)" class="flex-1 rounded-xl border border-current/10 bg-current/5 px-3.5 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" required />
            <button type="submit" class="rounded-xl bg-[#28543e] px-4 py-2 text-xs font-semibold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-xs hover:opacity-90">
                + Tambah
            </button>
        </form>
        @error('newName') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
        @error('delete') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
    </div>

    {{-- List Lokasi --}}
    <div class="rounded-2xl border border-current/10 bg-[#fffefa] shadow-xs dark:bg-[#1f2c25] divide-y divide-current/10">
        @foreach($locations as $loc)
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-current/5 text-sm dark:bg-current/5">
                        <x-heroicon-o-map-pin class="size-5 text-[#28543e] dark:text-[#bad6ae]" />
                    </span>
                    @if($editingId === $loc->id)
                        <form wire:submit="updateLocation" class="flex items-center gap-2">
                            <input type="text" wire:model="editName" class="rounded-lg border border-current/10 px-2.5 py-1 text-xs text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] dark:bg-current/5 dark:text-[#f3f1e8]" required />
                            <button type="submit" class="rounded-lg bg-[#28543e] px-2.5 py-1 text-xs text-white dark:bg-[#bad6ae] dark:text-[#151f1b]">Simpan</button>
                            <button type="button" wire:click="$set('editingId', null)" class="text-xs text-[#637067]">Batal</button>
                        </form>
                    @else
                        <div>
                            <span class="text-sm font-semibold text-[#263c32] dark:text-[#f3f1e8]">{{ $loc->name }}</span>
                            <span class="text-xs text-[#637067] ml-2">({{ $loc->assets_count }} aset, {{ $loc->issues_count }} kerusakan)</span>
                        </div>
                    @endif
                </div>

                @if($editingId !== $loc->id)
                    <div class="flex items-center gap-2">
                        <button wire:click="edit('{{ $loc->id }}')" class="text-xs text-[#637067] hover:text-[#263c32] dark:hover:text-[#f3f1e8]">
                            Edit
                        </button>
                        @if($loc->assets_count === 0)
                            <button wire:click="deleteLocation('{{ $loc->id }}')" wire:confirm="Hapus lokasi ini?" class="text-xs text-rose-500 hover:text-[#ad3f45]">
                                Hapus
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
