<div class="space-y-6 max-w-3xl mx-auto pb-20" @if ($list->status->value === 'active') wire:poll.10s.visible @endif>
    <!-- Top Nav & Header -->
    <div class="flex items-center justify-between">
        <a href="/organizer/shopping" class="text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white flex items-center gap-1">
            ← Daftar Belanja
        </a>

        <div class="flex items-center gap-2">
            @if ($list->status->value === 'active')
                <button type="button" wire:click="openCompleteModal" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-sm transition flex items-center gap-1.5">
                    ✓ Selesai Belanja
                </button>
            @else
                <button type="button" wire:click="shopAgain" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-sm transition flex items-center gap-1.5">
                    🔄 Belanja Lagi
                </button>
                <button type="button" wire:click="reopenList" class="px-3 py-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs rounded-xl">
                    Buka Lagi
                </button>
            @endif
        </div>
    </div>

    <!-- Title & Summary Card -->
    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $list->name }}</h1>
                    <span class="px-2 py-0.5 text-xs font-semibold rounded-md {{ $list->status->value === 'active' ? 'bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' }}">
                        {{ $list->status->label() }}
                    </span>
                </div>
                @if ($list->store)
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">🏪 Tempat: {{ $list->store }}</p>
                @endif
            </div>

            <div class="text-right sm:border-l sm:pl-6 border-slate-100 dark:border-slate-700">
                <span class="text-xs text-slate-400 block">Total Estimasi</span>
                <span class="text-xl font-bold text-slate-900 dark:text-white">
                    Rp {{ number_format($estimatedTotal, 0, ',', '.') }}
                </span>
                @if ($list->actual_total !== null)
                    <span class="text-xs text-emerald-600 dark:text-emerald-400 block font-medium">
                        Aktual: Rp {{ number_format($list->actual_total, 0, ',', '.') }}
                    </span>
                @endif
            </div>
        </div>

        @if ($list->status->value === 'active')
            <!-- Quick Add Item Form -->
            <form wire:submit.prevent="addItem" class="mt-6 pt-5 border-t border-slate-100 dark:border-slate-700/60 flex flex-col sm:flex-row gap-2">
                <div class="flex-1 relative">
                    <input 
                        type="text" 
                        wire:model="quickInput" 
                        list="popular-items-list"
                        placeholder="Ketik item... (cth: 'telur 2 kg', 'minyak 1 pouch')" 
                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white"
                        required
                    />
                    <datalist id="popular-items-list">
                        @foreach ($popularItems as $itemSuggestion)
                            <option value="{{ $itemSuggestion }}">
                        @endforeach
                    </datalist>
                </div>
                <input 
                    type="number" 
                    wire:model="quickEstimatedPrice" 
                    placeholder="Estimasi harga (Rp)" 
                    class="sm:w-44 px-3 py-2.5 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-white"
                />
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition flex-shrink-0">
                    + Tambah
                </button>
            </form>
        @endif
    </div>

    <!-- Unchecked Items List -->
    <div class="space-y-3">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">
            Perlu Dibeli ({{ $unchecked->count() }})
        </h3>

        @forelse ($unchecked as $item)
            <div class="bg-white dark:bg-slate-800 p-3.5 rounded-2xl shadow-xs border border-slate-200 dark:border-slate-700 flex items-center justify-between gap-3 group">
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <button 
                        type="button" 
                        wire:click="toggleItem('{{ $item->id }}')" 
                        class="w-8 h-8 rounded-lg border-2 border-slate-300 dark:border-slate-600 hover:border-indigo-500 flex items-center justify-center text-transparent hover:text-slate-300 transition"
                    >
                        <svg class="w-5 h-5 stroke-current stroke-2 fill-none" viewBox="0 0 24 24">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                    </button>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-sm text-slate-900 dark:text-white">{{ $item->name }}</span>
                            <span class="px-2 py-0.5 text-[11px] rounded bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                {{ $item->quantity + 0 }} {{ $item->unit ?: 'pcs' }}
                            </span>
                            <span class="text-[11px] text-slate-400">({{ $item->group->label() }})</span>
                        </div>
                        @if ($item->estimated_price)
                            <span class="text-xs text-slate-500 dark:text-slate-400 block mt-0.5">
                                @ Rp {{ number_format($item->estimated_price, 0, ',', '.') }} = Rp {{ number_format($item->quantity * $item->estimated_price, 0, ',', '.') }}
                            </span>
                        @endif
                    </div>
                </div>

                @if ($list->status->value === 'active')
                    <button type="button" wire:click="deleteItem('{{ $item->id }}')" class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-rose-500 p-1 text-sm">
                        ✕
                    </button>
                @endif
            </div>
        @empty
            <div class="p-6 bg-white dark:bg-slate-800 rounded-2xl text-center border border-slate-200 dark:border-slate-700">
                <p class="text-sm text-slate-500 dark:text-slate-400">Semua item sudah dicentang atau belum ada item.</p>
            </div>
        @endforelse
    </div>

    <!-- Checked Items List (Moved to Bottom with Avatar) -->
    @if ($checked->isNotEmpty())
        <div class="space-y-3 pt-4 border-t border-slate-200 dark:border-slate-700">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                Sudah Di Keranjang ({{ $checked->count() }})
            </h3>

            @foreach ($checked as $item)
                <div class="bg-slate-50 dark:bg-slate-800/40 p-3.5 rounded-2xl border border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between gap-3 opacity-75">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <button 
                            type="button" 
                            wire:click="toggleItem('{{ $item->id }}')" 
                            class="w-8 h-8 rounded-lg bg-emerald-500 border-2 border-emerald-500 text-white flex items-center justify-center transition"
                        >
                            <svg class="w-5 h-5 stroke-current stroke-2 fill-none" viewBox="0 0 24 24">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        </button>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="line-through text-sm font-medium text-slate-500 dark:text-slate-400">{{ $item->name }}</span>
                                <span class="text-[11px] text-slate-400">
                                    {{ $item->quantity + 0 }} {{ $item->unit ?: 'pcs' }}
                                </span>
                            </div>
                            @if ($item->checkedByUser)
                                <span class="text-[11px] text-emerald-600 dark:text-emerald-400 block mt-0.5">
                                    ✓ Dicentang oleh {{ $item->checkedByUser->name }} ({{ $item->checked_at?->format('H:i') }})
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Complete Shopping Modal -->
    @if ($showCompleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4 border border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Konfirmasi Selesai Belanja</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Catat pengeluaran belanja ini langsung ke laporan Keuangan keluarga.
                </p>

                <div class="space-y-3 text-sm">
                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Total Aktual Belanja (Rp)</label>
                        <input type="number" wire:model="actualTotal" class="w-full px-3 py-2 border rounded-xl dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white" required />
                    </div>

                    @if (config('modules.finance', false))
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Akun Pembayaran</label>
                            <select wire:model="expenseAccountId" class="w-full px-3 py-2 border rounded-xl dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                                @foreach ($accounts as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Kategori Pengeluaran</label>
                            <select wire:model="expenseCategoryId" class="w-full px-3 py-2 border rounded-xl dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                                @foreach ($categories as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($unchecked->isNotEmpty())
                        <div class="p-3 bg-amber-50 dark:bg-amber-950/30 rounded-xl border border-amber-200 dark:border-amber-800/50 space-y-2">
                            <span class="font-semibold text-xs text-amber-900 dark:text-amber-300 block">
                                Ada {{ $unchecked->count() }} item yang belum dicentang:
                            </span>
                            <div class="space-y-1 text-xs text-slate-700 dark:text-slate-300">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" wire:model="uncheckedAction" value="move_to_new" />
                                    <span>Pindahkan ke daftar belanja baru</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" wire:model="uncheckedAction" value="remove" />
                                    <span>Hapus item yang belum dibeli</span>
                                </label>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-2 pt-3">
                    <button type="button" wire:click="$set('showCompleteModal', false)" class="px-4 py-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl">
                        Batal
                    </button>
                    <button type="button" wire:click="completeShopping" class="px-4 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl">
                        Simpan & Selesai
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
