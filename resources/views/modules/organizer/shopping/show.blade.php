<div class="space-y-6 max-w-3xl mx-auto pb-20" @if ($list->status->value === 'active') wire:poll.10s.visible @endif>
    <!-- Top Nav & Header -->
    <div class="flex items-center justify-between">
        <a href="{{ route('organizer.shopping.index') }}" class="min-h-[44px] inline-flex items-center text-sm font-semibold text-[#637067] dark:text-[#637067] hover:text-[#263c32] dark:hover:text-white gap-1">
            ← Daftar Belanja
        </a>

        <div class="flex items-center gap-2">
            @if ($list->status->value === 'active')
                <button type="button" wire:click="openCompleteModal" class="px-4 py-2 bg-[#28543e] hover:opacity-90 text-white dark:bg-[#bad6ae] dark:text-[#151f1b] text-xs font-semibold rounded-xl shadow-sm transition flex items-center gap-1.5">
                    <x-heroicon-o-check class="mr-1 inline-block size-4 align-text-bottom" /> Selesai Belanja
                </button>
            @else
                <button type="button" wire:click="shopAgain" class="px-4 py-2 bg-[#28543e] hover:opacity-90 text-white dark:bg-[#bad6ae] dark:text-[#151f1b] text-xs font-semibold rounded-xl shadow-sm transition flex items-center gap-1.5">
                    <x-heroicon-o-arrow-path class="mr-1 inline-block size-4 align-text-bottom" /> Belanja Lagi
                </button>
                <button type="button" wire:click="reopenList" class="px-3 py-2 text-[#637067] dark:text-[#637067] hover:bg-current/5 text-xs rounded-xl">
                    Buka Lagi
                </button>
            @endif
        </div>
    </div>

    <!-- Title & Summary Card -->
    <div class="bg-[#fffefa] dark:bg-[#1f2c25] p-6 rounded-2xl shadow-sm border border-current/10">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">{{ $list->name }}</h1>
                    <span class="px-2 py-0.5 text-xs font-semibold rounded-md {{ $list->status->value === 'active' ? 'bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300' : 'bg-[#28543e]/15 text-[#28543e] dark:bg-[#bad6ae]/15 dark:text-[#bad6ae]' }}">
                        {{ $list->status->label() }}
                    </span>
                </div>
                @if ($list->store)
                    <p class="text-sm text-[#637067] dark:text-[#637067] mt-1"><x-heroicon-o-shopping-bag class="mr-1 inline-block size-4 align-text-bottom" /> Tempat: {{ $list->store }}</p>
                @endif
            </div>

            <div class="text-right sm:border-l sm:pl-6 border-current/10">
                <span class="text-xs text-[#637067] block">Total Estimasi</span>
                <span class="text-xl font-bold text-[#263c32] dark:text-[#f3f1e8]">
                    Rp {{ number_format($estimatedTotal, 0, ',', '.') }}
                </span>
                @if ($list->actual_total !== null)
                    <span class="text-xs text-[#28543e] dark:text-[#bad6ae] block font-medium">
                        Aktual: Rp {{ number_format($list->actual_total, 0, ',', '.') }}
                    </span>
                @endif
            </div>
        </div>

        @if ($list->status->value === 'active')
            <!-- Quick Add Item Form -->
            <form wire:submit.prevent="addItem" class="mt-6 pt-5 border-t border-current/10 flex flex-col sm:flex-row gap-2">
                <div class="flex-1 relative">
                    <input 
                        type="text" 
                        wire:model="quickInput" 
                        list="popular-items-list"
                        placeholder="Ketik item... (cth: 'telur 2 kg', 'minyak 1 pouch')" 
                        class="w-full px-4 py-2.5 bg-current/5 dark:bg-current/5 border border-current/10 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#28543e] dark:focus:ring-[#bad6ae] text-[#263c32] dark:text-[#f3f1e8]"
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
                    class="sm:w-44 px-3 py-2.5 bg-current/5 dark:bg-current/5 border border-current/10 rounded-xl text-sm text-[#263c32] dark:text-[#f3f1e8]"
                />
                <button type="submit" class="px-5 py-2.5 bg-[#28543e] hover:opacity-90 text-white dark:bg-[#bad6ae] dark:text-[#151f1b] text-sm font-semibold rounded-xl transition flex-shrink-0">
                    + Tambah
                </button>
            </form>
        @endif
    </div>

    <!-- Unchecked Items List -->
    <div class="space-y-3">
        <h3 class="text-xs font-bold uppercase tracking-wider text-[#637067]">
            Perlu Dibeli ({{ $unchecked->count() }})
        </h3>

        @forelse ($unchecked as $item)
            <div class="bg-[#fffefa] dark:bg-[#1f2c25] p-3.5 rounded-2xl shadow-xs border border-current/10 flex items-center justify-between gap-3 group">
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <button 
                        type="button" 
                        wire:click="toggleItem('{{ $item->id }}')" 
                        aria-label="{{ __('Tandai sudah dibeli') }}"
                        class="w-11 h-11 shrink-0 rounded-xl border-2 border-current/15 hover:border-[#28543e] dark:border-[#bad6ae] flex items-center justify-center text-transparent hover:text-[#637067] transition"
                    >
                        <svg class="w-5 h-5 stroke-current stroke-2 fill-none" viewBox="0 0 24 24">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                    </button>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-sm text-[#263c32] dark:text-[#f3f1e8]">{{ $item->name }}</span>
                            <span class="px-2 py-0.5 text-[11px] rounded bg-current/5 dark:bg-current/10 text-[#637067] dark:text-[#b2beb2]">
                                {{ $item->quantity + 0 }} {{ $item->unit ?: 'pcs' }}
                            </span>
                            <span class="text-[11px] text-[#637067]">({{ $item->group->label() }})</span>
                        </div>
                        @if ($item->estimated_price)
                            <span class="text-xs text-[#637067] dark:text-[#637067] block mt-0.5">
                                @ Rp {{ number_format($item->estimated_price, 0, ',', '.') }} = Rp {{ number_format($item->quantity * $item->estimated_price, 0, ',', '.') }}
                            </span>
                        @endif
                    </div>
                </div>

                @if ($list->status->value === 'active')
                    <button type="button" wire:click="deleteItem('{{ $item->id }}')" class="opacity-0 group-hover:opacity-100 text-[#637067] hover:text-rose-500 p-1 text-sm">
                        <x-heroicon-o-x-mark class="size-4" />
                    </button>
                @endif
            </div>
        @empty
            <div class="p-6 bg-[#fffefa] dark:bg-[#1f2c25] rounded-2xl text-center border border-current/10">
                <p class="text-sm text-[#637067] dark:text-[#637067]">Semua item sudah dicentang atau belum ada item.</p>
            </div>
        @endforelse
    </div>

    <!-- Checked Items List (Moved to Bottom with Avatar) -->
    @if ($checked->isNotEmpty())
        <div class="space-y-3 pt-4 border-t border-current/10">
            <h3 class="text-xs font-bold uppercase tracking-wider text-[#637067]">
                Sudah Di Keranjang ({{ $checked->count() }})
            </h3>

            @foreach ($checked as $item)
                <div class="bg-current/5 dark:bg-[#1f2c25]/40 p-3.5 rounded-2xl border border-current/10/60 flex items-center justify-between gap-3 opacity-75">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <button 
                            type="button" 
                            wire:click="toggleItem('{{ $item->id }}')" 
                            aria-label="{{ __('Batal centang') }}"
                            class="w-11 h-11 shrink-0 rounded-xl bg-[#28543e] dark:bg-[#bad6ae] border-2 border-[#28543e] dark:border-[#bad6ae] text-white dark:text-[#151f1b] flex items-center justify-center transition"
                        >
                            <svg class="w-5 h-5 stroke-current stroke-2 fill-none" viewBox="0 0 24 24">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        </button>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="line-through text-sm font-medium text-[#637067] dark:text-[#637067]">{{ $item->name }}</span>
                                <span class="text-[11px] text-[#637067]">
                                    {{ $item->quantity + 0 }} {{ $item->unit ?: 'pcs' }}
                                </span>
                            </div>
                            @if ($item->checkedByUser)
                                <span class="text-[11px] text-[#28543e] dark:text-[#bad6ae] block mt-0.5">
                                    Dicentang oleh {{ $item->checkedByUser->name }} ({{ $item->checked_at?->format('H:i') }})
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
            <div class="bg-[#fffefa] dark:bg-[#1f2c25] rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4 border border-current/10">
                <h3 class="text-lg font-bold text-[#263c32] dark:text-[#f3f1e8]">Konfirmasi Selesai Belanja</h3>
                <p class="text-sm text-[#637067] dark:text-[#b2beb2]">
                    Catat pengeluaran belanja ini langsung ke laporan Keuangan keluarga.
                </p>

                <div class="space-y-3 text-sm">
                    <div>
                        <label class="block font-medium text-[#263c32] dark:text-[#b2beb2] mb-1">Total Aktual Belanja (Rp)</label>
                        <input type="number" wire:model="actualTotal" class="w-full px-3 py-2 border rounded-xl dark:bg-[#1f2c25] border-current/10 text-[#263c32] dark:text-[#f3f1e8]" required />
                    </div>

                    @if (config('modules.finance', false))
                        <div>
                            <label class="block font-medium text-[#263c32] dark:text-[#b2beb2] mb-1">Akun Pembayaran</label>
                            <select wire:model="expenseAccountId" class="w-full px-3 py-2 border rounded-xl dark:bg-[#1f2c25] border-current/10 text-[#263c32] dark:text-[#f3f1e8]">
                                @foreach ($accounts as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-[#263c32] dark:text-[#b2beb2] mb-1">Kategori Pengeluaran</label>
                            <select wire:model="expenseCategoryId" class="w-full px-3 py-2 border rounded-xl dark:bg-[#1f2c25] border-current/10 text-[#263c32] dark:text-[#f3f1e8]">
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
                            <div class="space-y-1 text-xs text-[#263c32] dark:text-[#b2beb2]">
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
                    <button type="button" wire:click="$set('showCompleteModal', false)" class="px-4 py-2 text-sm text-[#637067] dark:text-[#637067] hover:bg-current/5 rounded-xl">
                        Batal
                    </button>
                    <button type="button" wire:click="completeShopping" class="px-4 py-2 text-sm font-semibold text-white bg-[#28543e] dark:bg-[#bad6ae] hover:opacity-90 rounded-xl">
                        Simpan & Selesai
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
