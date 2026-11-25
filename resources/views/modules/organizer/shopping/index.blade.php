<div class="space-y-6 max-w-4xl mx-auto pb-16">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">Daftar Belanja Bersama</h1>
            <p class="text-sm text-[#637067] dark:text-[#637067]">Belanja kolaboratif bersama pasangan, centang realtime, dan langsung catat pengeluaran.</p>
        </div>
    </div>

    <!-- Create New List Form -->
    <div class="bg-[#fffefa] dark:bg-[#1f2c25] p-4 rounded-2xl shadow-sm border border-current/10">
        <h3 class="text-xs font-bold tracking-wider uppercase text-[#637067] mb-3">Buat Daftar Belanja Baru</h3>
        <form wire:submit.prevent="createList" class="flex flex-col sm:flex-row gap-2">
            <input 
                type="text" 
                wire:model="newListName" 
                placeholder="Nama daftar (cth: Belanja Mingguan, Pasar Pagi)" 
                class="flex-1 px-4 py-2.5 bg-current/5 dark:bg-current/5 border border-current/10 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#28543e] dark:focus:ring-[#bad6ae] text-[#263c32] dark:text-[#f3f1e8]"
                required
            />
            <input 
                type="text" 
                wire:model="newListStore" 
                placeholder="Toko / Tempat (opsional)" 
                class="sm:w-56 px-4 py-2.5 bg-current/5 dark:bg-current/5 border border-current/10 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#28543e] dark:focus:ring-[#bad6ae] text-[#263c32] dark:text-[#f3f1e8]"
            />
            <button type="submit" class="px-5 py-2.5 bg-[#28543e] hover:opacity-90 text-white dark:bg-[#bad6ae] dark:text-[#151f1b] text-sm font-semibold rounded-xl transition flex-shrink-0">
                + Buat Daftar
            </button>
        </form>
    </div>

    <!-- View Switcher Tabs -->
    <div class="flex items-center gap-1 border-b border-current/10 pb-2">
        <button wire:click="$set('view', 'active')" class="px-3.5 py-1.5 rounded-lg text-sm font-semibold transition {{ $view === 'active' ? 'bg-[#28543e]/15 dark:bg-[#bad6ae]/20 text-[#28543e] dark:text-[#bad6ae]' : 'text-[#637067] dark:text-[#637067] hover:bg-current/5 ' }}">
            Daftar Aktif
        </button>
        <button wire:click="$set('view', 'history')" class="px-3.5 py-1.5 rounded-lg text-sm font-semibold transition {{ $view === 'history' ? 'bg-[#28543e]/15 dark:bg-[#bad6ae]/20 text-[#28543e] dark:text-[#bad6ae]' : 'text-[#637067] dark:text-[#637067] hover:bg-current/5 ' }}">
            Riwayat Selesai
        </button>
    </div>

    <!-- Lists Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @forelse ($lists as $list)
            <div class="bg-[#fffefa] dark:bg-[#1f2c25] p-5 rounded-2xl shadow-sm border border-current/10 hover:border-current/25 transition flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <span class="px-2 py-0.5 text-xs font-semibold rounded-md {{ $list->status->value === 'active' ? 'bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300' : 'bg-[#28543e]/15 text-[#28543e] dark:bg-[#bad6ae]/15 dark:text-[#bad6ae]' }}">
                            {{ $list->status->label() }}
                        </span>
                        <button type="button"
                            wire:click="deleteList('{{ $list->id }}')"
                            wire:confirm="Yakin ingin menghapus daftar belanja ini?"
                            aria-label="Hapus daftar belanja {{ $list->name }}"
                            title="Hapus daftar"
                            class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-xl text-[#637067] hover:bg-rose-500/10 hover:text-rose-500 transition-colors">
                            <x-heroicon-o-trash class="size-4" />
                        </button>
                    </div>

                    <a href="{{ route('organizer.shopping.show', $list) }}" class="block mt-2">
                        <h3 class="text-lg font-bold text-[#263c32] dark:text-[#f3f1e8] hover:text-[#28543e] dark:hover:text-[#bad6ae]">
                            {{ $list->name }}
                        </h3>
                    </a>

                    @if ($list->store)
                        <p class="text-xs text-[#637067] dark:text-[#637067] mt-0.5"><x-heroicon-o-shopping-bag class="mr-1 inline-block size-4 align-text-bottom" /> {{ $list->store }}</p>
                    @endif

                    <div class="mt-4 flex items-center justify-between text-xs text-[#637067] dark:text-[#b2beb2]">
                        <span>Progress: {{ $list->checked_items }} / {{ $list->total_items }} item</span>
                        <span>{{ $list->total_items > 0 ? round(($list->checked_items / $list->total_items) * 100) : 0 }}%</span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full bg-current/5 dark:bg-current/10 h-2 rounded-full mt-1.5 overflow-hidden">
                        <div class="bg-[#28543e] dark:bg-[#bad6ae] h-full rounded-full transition-all" style="width: {{ $list->total_items > 0 ? ($list->checked_items / $list->total_items) * 100 : 0 }}%"></div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-current/10 flex items-center justify-between text-xs text-[#637067]">
                    <span>Oleh: {{ $list->creator?->name }}</span>
                    <a href="{{ route('organizer.shopping.show', $list) }}" class="font-semibold text-[#28543e] dark:text-[#bad6ae] hover:underline">
                        Buka Daftar →
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-[#fffefa] dark:bg-[#1f2c25] rounded-2xl p-12 text-center border border-current/10">
                <p class="text-[#637067] dark:text-[#637067]">Belum ada daftar belanja pada tampilan ini.</p>
            </div>
        @endforelse
    </div>
</div>
