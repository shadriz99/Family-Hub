<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-[#263c32] dark:text-[#f3f1e8]">Kategori Keuangan</h1>
            <p class="text-xs sm:text-sm text-current/60">Struktur pos pengeluaran, pemasukan, dan alokasi anggaran rumah tangga</p>
        </div>

        <button wire:click="openCreate(null)"
                class="inline-flex items-center gap-1.5 rounded-2xl bg-[#28543e] px-4 py-2 text-xs sm:text-sm font-bold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-sm transition-all hover:opacity-90 active:scale-95 self-start sm:self-auto">
            <x-heroicon-o-plus class="size-4" />
            <span>Tambah Kategori Induk</span>
        </button>
    </div>

    @if (session()->has('message'))
        <div class="rounded-2xl bg-[#28543e] dark:bg-[#bad6ae]/10 p-4 text-xs sm:text-sm font-semibold text-[#28543e] dark:text-[#bad6ae]">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="rounded-2xl bg-rose-500/10 p-4 text-xs sm:text-sm font-semibold text-[#ad3f45] dark:text-rose-300">
            {{ session('error') }}
        </div>
    @endif

    {{-- Tabs --}}
    <div class="flex gap-2 border-b border-current/10 pb-2">
        <button wire:click="selectTab('expense')"
                class="rounded-xl px-4 py-2 text-xs sm:text-sm font-bold transition-colors {{ $activeTab === 'expense' ? 'bg-[#28543e] text-white dark:bg-[#bad6ae] dark:text-[#151f1b]' : 'text-current/60 hover:bg-current/10' }}">
            Pengeluaran
        </button>
        <button wire:click="selectTab('income')"
                class="rounded-xl px-4 py-2 text-xs sm:text-sm font-bold transition-colors {{ $activeTab === 'income' ? 'bg-[#28543e] text-white dark:bg-[#bad6ae] dark:text-[#151f1b]' : 'text-current/60 hover:bg-current/10' }}">
            Pemasukan
        </button>
    </div>

    {{-- Categories List --}}
    <div class="space-y-4">
        @forelse($categories as $category)
            <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-sm transition-colors dark:bg-[#1f2c25] {{ $category->isArchived() ? 'opacity-60' : '' }}">
                {{-- Parent Category Header --}}
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-current/10 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-[#28543e]/10 dark:bg-[#bad6ae]/15 text-[#28543e] dark:text-[#bad6ae]">
                            <x-heroicon-o-folder class="size-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-[#263c32] dark:text-[#f3f1e8]">{{ $category->name }}</h3>
                                @if($category->isSystem())
                                    <span class="rounded-full bg-blue-500/10 px-2 py-0.5 text-[10px] font-bold text-blue-600 dark:text-blue-400">Sistem</span>
                                @endif
                                @if($category->isArchived())
                                    <span class="rounded-full bg-rose-500/10 px-2 py-0.5 text-[10px] font-bold text-[#ad3f45] dark:text-[#ffafb1]">Diarsipkan</span>
                                @endif
                            </div>
                            @if($category->default_allocation)
                                <span class="text-xs font-semibold text-[#28543e] dark:text-[#bad6ae]">
                                    Alokasi: {{ $category->default_allocation->label() }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-2 self-end sm:self-auto">
                        <button wire:click="openCreate('{{ $category->id }}')" class="rounded-xl bg-current/5 px-2.5 py-1 text-xs font-semibold text-current/80 hover:bg-current/10">
                            + Sub-kategori
                        </button>
                        <button wire:click="openEdit('{{ $category->id }}')" class="rounded-xl px-2.5 py-1 text-xs font-semibold text-current/70 hover:bg-current/10">
                            Edit
                        </button>
                        @if(!$category->isSystem())
                            <button wire:click="openMerge('{{ $category->id }}')" class="rounded-xl px-2.5 py-1 text-xs font-semibold text-[#87571f] hover:bg-amber-500/10">
                                Gabung
                            </button>
                            <button wire:click="toggleArchive('{{ $category->id }}')" class="rounded-xl px-2.5 py-1 text-xs font-semibold text-[#ad3f45] hover:bg-rose-500/10">
                                {{ $category->isArchived() ? 'Aktifkan' : 'Arsip' }}
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Children / Subcategories --}}
                @if($category->children->isNotEmpty())
                    <div class="mt-3 divide-y divide-current/5 pl-4 sm:pl-8">
                        @foreach($category->children as $child)
                            <div class="flex items-center justify-between py-2.5 {{ $child->isArchived() ? 'opacity-50' : '' }}">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold text-[#263c32] dark:text-[#f3f1e8]">{{ $child->name }}</span>
                                        @if($child->isSystem())
                                            <span class="rounded bg-blue-500/10 px-1.5 py-0.2 text-[9px] font-bold text-blue-600">Sistem</span>
                                        @endif
                                        @if($child->isArchived())
                                            <span class="rounded bg-rose-500/10 px-1.5 py-0.2 text-[9px] font-bold text-[#ad3f45]">Arsip</span>
                                        @endif
                                    </div>
                                    @if($child->default_allocation)
                                        <div class="text-[11px] text-current/50">{{ $child->default_allocation->label() }}</div>
                                    @endif
                                </div>

                                <div class="flex items-center gap-1.5">
                                    <button wire:click="openEdit('{{ $child->id }}')" class="rounded-lg px-2 py-0.5 text-xs text-current/60 hover:bg-current/10">
                                        Edit
                                    </button>
                                    @if(!$child->isSystem())
                                        <button wire:click="openMerge('{{ $child->id }}')" class="rounded-lg px-2 py-0.5 text-xs text-[#87571f] hover:bg-amber-500/10">
                                            Gabung
                                        </button>
                                        <button wire:click="toggleArchive('{{ $child->id }}')" class="rounded-lg px-2 py-0.5 text-xs text-[#ad3f45] hover:bg-rose-500/10">
                                            {{ $child->isArchived() ? 'Aktifkan' : 'Arsip' }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-2 text-xs italic text-current/40 pl-4 sm:pl-8">
                        Belum ada rincian sub-kategori.
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-3xl border border-dashed border-current/15 p-8 text-center text-xs text-current/60">
                Belum ada kategori {{ $activeTab === 'expense' ? 'pengeluaran' : 'pemasukan' }}.
            </div>
        @endforelse
    </div>

    {{-- Form Modal --}}
    @if($showFormModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-3xl border border-current/10 bg-[#fffefa] p-6 shadow-2xl transition-colors dark:bg-[#1f2c25]">
                <h3 class="text-lg font-black text-[#263c32] dark:text-[#f3f1e8]">
                    {{ $editingCategoryId ? 'Edit Kategori' : ($parent_id ? 'Tambah Sub-kategori' : 'Tambah Kategori Induk') }}
                </h3>

                <form wire:submit.prevent="saveCategory" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-current/70">Nama Kategori</label>
                        <input type="text" wire:model="name" placeholder="mis. Belanja Dapur, Kopi & Jajan"
                               class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                        @error('name') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
                    </div>

                    @if($type === 'expense')
                        <div>
                            <label class="block text-xs font-bold text-current/70">Alokasi Default</label>
                            <select wire:model="default_allocation"
                                    class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none">
                                <option value="living">Kebutuhan Hidup (Makan, Operasional, Transportasi)</option>
                                <option value="tertiary">Tersier (Hiburan, Hobi, Skincare, Dining Out)</option>
                                <option value="debt">Cicilan / Utang (KPR, Kendaraan, Utang)</option>
                                <option value="savings">Tabungan & Investasi (Dana Darurat, Investasi)</option>
                                <option value="social">Donasi / Sosial / Zakat (Zakat, Infak, Sedekah)</option>
                            </select>
                            @error('default_allocation') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div class="mt-6 flex items-center justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showFormModal', false)"
                                class="rounded-xl px-4 py-2 text-xs font-bold text-current/70 hover:bg-current/10">
                            Batal
                        </button>
                        <button type="submit"
                                class="rounded-xl bg-[#28543e] px-5 py-2 text-xs font-bold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-sm hover:opacity-90">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Merge Modal --}}
    @if($showMergeModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-3xl border border-current/10 bg-[#fffefa] p-6 shadow-2xl transition-colors dark:bg-[#1f2c25]">
                <h3 class="text-lg font-black text-[#263c32] dark:text-[#f3f1e8]">Gabungkan Kategori</h3>
                <p class="mt-1 text-xs text-current/60">
                    Semua transaksi dan anggaran dari kategori ini akan dipindahkan ke kategori tujuan, lalu kategori asal diarsipkan.
                </p>

                <form wire:submit.prevent="executeMerge" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-current/70">Pilih Kategori Tujuan</label>
                        <select wire:model="mergeTargetId"
                                class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none">
                            <option value="">-- Pilih Kategori Tujuan --</option>
                            @foreach($mergeOptions as $opt)
                                <option value="{{ $opt->id }}">{{ $opt->parent ? "{$opt->parent->name} > " : '' }}{{ $opt->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showMergeModal', false)"
                                class="rounded-xl px-4 py-2 text-xs font-bold text-current/70 hover:bg-current/10">
                            Batal
                        </button>
                        <button type="submit"
                                class="rounded-xl bg-amber-600 px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-amber-700">
                            Gabungkan Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
