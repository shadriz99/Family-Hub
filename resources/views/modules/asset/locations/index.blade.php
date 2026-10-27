<div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('assets.index') }}" class="text-xs text-stone-500 hover:text-stone-900 dark:hover:text-white">
                    ← Kembali ke Aset
                </a>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900 dark:text-white">
                Daftar Lokasi & Ruangan Rumah
            </h1>
            <p class="text-sm text-stone-500 dark:text-stone-400">
                Kelola area atau ruangan untuk mengelompokkan peralatan dan aset keluarga
            </p>
        </div>
    </div>

    {{-- Sub Navigation Tabs --}}
    <div class="mb-6 flex flex-wrap gap-2 border-b border-stone-200 pb-3 dark:border-stone-800">
        <a href="{{ route('assets.index') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">
            📦 Aset
        </a>
        <a href="{{ route('assets.maintenance') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">
            🔧 Perawatan
        </a>
        <a href="{{ route('assets.documents') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">
            📄 Dokumen & Pajak
        </a>
        <a href="{{ route('assets.issues') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">
            🛠️ Kerusakan
        </a>
        <a href="{{ route('assets.vendors') }}" class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800">
            👨‍🔧 Vendor / Teknisi
        </a>
        <a href="{{ route('assets.locations') }}" class="rounded-lg bg-emerald-50 px-3.5 py-1.5 text-sm font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
            📍 Lokasi ({{ $locations->count() }})
        </a>
    </div>

    {{-- Form Tambah Lokasi Cepat --}}
    <div class="mb-6 rounded-2xl border border-stone-200 bg-white p-4 shadow-xs dark:border-stone-800 dark:bg-stone-900">
        <h2 class="text-xs font-semibold text-stone-800 dark:text-stone-200 mb-2">Tambah Ruangan / Lokasi Baru</h2>
        <form wire:submit="createLocation" class="flex gap-2">
            <input type="text" wire:model="newName" placeholder="Nama ruangan (mis. Balkon, Gudang Belakang, Rooftop)" class="flex-1 rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" required />
            <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-emerald-500">
                + Tambah
            </button>
        </form>
        @error('newName') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
        @error('delete') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
    </div>

    {{-- List Lokasi --}}
    <div class="rounded-2xl border border-stone-200 bg-white shadow-xs dark:border-stone-800 dark:bg-stone-900 divide-y divide-stone-100 dark:divide-stone-800">
        @foreach($locations as $loc)
            <div class="flex items-center justify-between p-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-stone-100 text-sm dark:bg-stone-800">
                        📍
                    </span>
                    @if($editingId === $loc->id)
                        <form wire:submit="updateLocation" class="flex items-center gap-2">
                            <input type="text" wire:model="editName" class="rounded-lg border border-stone-200 px-2.5 py-1 text-xs text-stone-900 focus:border-emerald-500 dark:bg-stone-800 dark:text-white" required />
                            <button type="submit" class="rounded-lg bg-emerald-600 px-2.5 py-1 text-xs text-white">Simpan</button>
                            <button type="button" wire:click="$set('editingId', null)" class="text-xs text-stone-400">Batal</button>
                        </form>
                    @else
                        <div>
                            <span class="text-sm font-semibold text-stone-900 dark:text-white">{{ $loc->name }}</span>
                            <span class="text-xs text-stone-400 ml-2">({{ $loc->assets_count }} aset, {{ $loc->issues_count }} kerusakan)</span>
                        </div>
                    @endif
                </div>

                @if($editingId !== $loc->id)
                    <div class="flex items-center gap-2">
                        <button wire:click="edit('{{ $loc->id }}')" class="text-xs text-stone-500 hover:text-stone-700 dark:hover:text-stone-300">
                            Edit
                        </button>
                        @if($loc->assets_count === 0)
                            <button wire:click="deleteLocation('{{ $loc->id }}')" wire:confirm="Hapus lokasi ini?" class="text-xs text-rose-500 hover:text-rose-700">
                                Hapus
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
