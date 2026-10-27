<div class="space-y-6 max-w-4xl mx-auto pb-16">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Daftar Belanja Bersama</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Belanja kolaboratif bersama pasangan, centang realtime, dan langsung catat pengeluaran.</p>
        </div>
    </div>

    <!-- Create New List Form -->
    <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
        <h3 class="text-xs font-bold tracking-wider uppercase text-slate-400 mb-3">Buat Daftar Belanja Baru</h3>
        <form wire:submit.prevent="createList" class="flex flex-col sm:flex-row gap-2">
            <input 
                type="text" 
                wire:model="newListName" 
                placeholder="Nama daftar (cth: Belanja Mingguan, Pasar Pagi)" 
                class="flex-1 px-4 py-2.5 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white"
                required
            />
            <input 
                type="text" 
                wire:model="newListStore" 
                placeholder="Toko / Tempat (opsional)" 
                class="sm:w-56 px-4 py-2.5 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white"
            />
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition flex-shrink-0">
                + Buat Daftar
            </button>
        </form>
    </div>

    <!-- View Switcher Tabs -->
    <div class="flex items-center gap-1 border-b border-slate-200 dark:border-slate-700 pb-2">
        <button wire:click="$set('view', 'active')" class="px-3.5 py-1.5 rounded-lg text-sm font-semibold transition {{ $view === 'active' ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
            Daftar Aktif
        </button>
        <button wire:click="$set('view', 'history')" class="px-3.5 py-1.5 rounded-lg text-sm font-semibold transition {{ $view === 'history' ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
            Riwayat Selesai
        </button>
    </div>

    <!-- Lists Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @forelse ($lists as $list)
            <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 transition flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <span class="px-2 py-0.5 text-xs font-semibold rounded-md {{ $list->status->value === 'active' ? 'bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' }}">
                            {{ $list->status->label() }}
                        </span>
                        <button type="button" wire:click="deleteList('{{ $list->id }}')" title="Hapus daftar" class="text-slate-400 hover:text-rose-500 text-sm">
                            🗑️
                        </button>
                    </div>

                    <a href="/organizer/shopping/{{ $list->id }}" class="block mt-2">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400">
                            {{ $list->name }}
                        </h3>
                    </a>

                    @if ($list->store)
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">🏪 {{ $list->store }}</p>
                    @endif

                    <div class="mt-4 flex items-center justify-between text-xs text-slate-600 dark:text-slate-300">
                        <span>Progress: {{ $list->checked_items }} / {{ $list->total_items }} item</span>
                        <span>{{ $list->total_items > 0 ? round(($list->checked_items / $list->total_items) * 100) : 0 }}%</span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full bg-slate-100 dark:bg-slate-700 h-2 rounded-full mt-1.5 overflow-hidden">
                        <div class="bg-indigo-600 h-full rounded-full transition-all" style="width: {{ $list->total_items > 0 ? ($list->checked_items / $list->total_items) * 100 : 0 }}%"></div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between text-xs text-slate-400">
                    <span>Oleh: {{ $list->creator?->name }}</span>
                    <a href="/organizer/shopping/{{ $list->id }}" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                        Buka Daftar →
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-slate-800 rounded-2xl p-12 text-center border border-slate-200 dark:border-slate-700">
                <p class="text-slate-500 dark:text-slate-400">Belum ada daftar belanja pada tampilan ini.</p>
            </div>
        @endforelse
    </div>
</div>
