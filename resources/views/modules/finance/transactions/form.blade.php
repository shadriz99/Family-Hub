<div class="mx-auto max-w-xl space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-[#263c32] dark:text-[#f3f1e8]">Catat Transaksi</h1>
            <p class="text-xs sm:text-sm text-current/60">Pengeluaran cepat, pemasukan, atau transfer saldo</p>
        </div>

        <a href="{{ route('finance.transactions.index') }}" class="text-xs font-bold text-current/60 hover:underline">
            &larr; Kembali
        </a>
    </div>

    {{-- Type Selector Tabs --}}
    <div class="flex rounded-2xl border border-current/10 bg-current/5 p-1">
        <button wire:click="$set('type', 'expense'); $set('mode', 'quick')"
                class="flex-1 rounded-xl py-2 text-xs sm:text-sm font-bold transition-all {{ $type === 'expense' ? 'bg-rose-600 text-white shadow-sm' : 'text-current/70 hover:bg-current/10' }}">
            Pengeluaran
        </button>
        <button wire:click="$set('type', 'income'); $set('mode', 'quick')"
                class="flex-1 rounded-xl py-2 text-xs sm:text-sm font-bold transition-all {{ $type === 'income' ? 'bg-emerald-600 text-white shadow-sm' : 'text-current/70 hover:bg-current/10' }}">
            Pemasukan
        </button>
        <button wire:click="$set('type', 'transfer'); $set('mode', 'transfer')"
                class="flex-1 rounded-xl py-2 text-xs sm:text-sm font-bold transition-all {{ $type === 'transfer' ? 'bg-blue-600 text-white shadow-sm' : 'text-current/70 hover:bg-current/10' }}">
            Transfer
        </button>
    </div>

    {{-- Scan Receipt Banner / Trigger (US-FIN-01 AC5) --}}
    @if($type === 'expense')
        <div class="flex items-center justify-between rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-3.5">
            <div class="flex items-center gap-2.5">
                <x-heroicon-o-camera class="size-5 text-emerald-600 dark:text-emerald-400" />
                <div class="text-xs font-semibold text-emerald-900 dark:text-emerald-200">
                    <span>Pindai Struk Belanja (OCR Lokal)</span>
                    <span class="block text-[10px] text-current/70">Otomatis deteksi merchant, tanggal, & total</span>
                </div>
            </div>

            <label class="cursor-pointer rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 active:scale-95">
                <span>Foto Struk</span>
                <input type="file" wire:model="receipt_file" accept="image/*" capture="environment" class="hidden" />
            </label>
        </div>

        @if($receipt_ocr_summary)
            <div class="rounded-2xl bg-blue-500/10 p-3 text-xs font-semibold text-blue-700 dark:text-blue-300">
                {{ $receipt_ocr_summary }}
            </div>
        @endif
    @endif

    {{-- Main Form Card --}}
    <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 sm:p-7 shadow-sm transition-colors dark:bg-[#1f2c25]">
        {{-- Amount Input (Autofocus, supports "45rb", "1,2jt") --}}
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-current/60">Nominal (Rp)</label>
            <div class="relative mt-2">
                <input type="text" wire:model="amount" autofocus placeholder="0 (mis. 45rb, 1,2jt)"
                       class="w-full rounded-2xl border-2 border-current/20 bg-transparent px-4 py-3 text-2xl sm:text-3xl font-black tracking-tight focus:border-emerald-500 focus:outline-none" />
            </div>
            @error('amount') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
        </div>

        {{-- Account Selector --}}
        <div class="mt-4">
            <label class="block text-xs font-bold text-current/70">
                {{ $type === 'transfer' ? 'Dari Akun Sumber' : 'Menggunakan Akun' }}
            </label>
            <select wire:model="account_id"
                    class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm font-semibold focus:border-emerald-500 focus:outline-none">
                @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->type->label() }})</option>
                @endforeach
            </select>
            @error('account_id') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
        </div>

        {{-- Quick Mode vs Detail Mode Toggle --}}
        @if($type !== 'transfer')
            <div class="mt-5 flex items-center justify-between border-t border-current/10 pt-4">
                <span class="text-xs font-bold text-current/70">
                    {{ $mode === 'quick' ? 'Mode Cepat (1-Tap Simpan)' : 'Mode Rincian Lengkap' }}
                </span>
                <button type="button" wire:click="$set('mode', '{{ $mode === 'quick' ? 'detail' : 'quick' }}')"
                        class="text-xs font-bold text-emerald-600 underline dark:text-emerald-400">
                    {{ $mode === 'quick' ? 'Buka Detail Field &rarr;' : 'Tutup ke Mode Cepat' }}
                </button>
            </div>

            {{-- Mode Cepat: Top 8 Quick Categories (US-FIN-01 AC1 & AC2) --}}
            @if($mode === 'quick')
                <div class="mt-4">
                    <label class="block text-xs font-semibold text-current/60 mb-2">Tap kategori untuk langsung menyimpan:</label>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        @foreach($quickCategories as $qCat)
                            <button type="button" wire:click="quickSaveCategory('{{ $qCat->id }}')"
                                    class="flex flex-col items-center justify-center rounded-2xl border border-current/10 bg-current/5 p-3 text-center transition-all hover:bg-emerald-500/10 hover:border-emerald-500/50 active:scale-95">
                                <span class="text-xs font-bold truncate max-w-full text-[#263c32] dark:text-[#f3f1e8]">{{ $qCat->name }}</span>
                                @if($qCat->parent)
                                    <span class="text-[10px] text-current/50 truncate max-w-full">{{ $qCat->parent->name }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif

        {{-- Transfer Mode Inputs (US-FIN-02) --}}
        @if($type === 'transfer')
            <div class="mt-4 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-current/70">Ke Akun Tujuan</label>
                    <select wire:model="to_account_id"
                            class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm font-semibold focus:border-emerald-500 focus:outline-none">
                        <option value="">-- Pilih Akun Tujuan --</option>
                        @foreach($accounts as $acc)
                            @if($acc->id !== $account_id)
                                <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->type->label() }})</option>
                            @endif
                        @endforeach
                    </select>
                    @error('to_account_id') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-current/70">Biaya Admin Transfer (Opsional)</label>
                    <input type="text" wire:model="admin_fee" placeholder="mis. 2.500 atau 6500"
                           class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" />
                    <p class="text-[11px] text-current/50 mt-0.5">Otomatis dicatat sebagai pengeluaran terpisah (Biaya Admin Bank).</p>
                </div>

                {{-- Savings allocation goal --}}
                <div>
                    <label class="block text-xs font-bold text-current/70">Tujuan Penempatan Dana (Tabungan & Investasi)</label>
                    <select wire:model="allocation_detail"
                            class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        <option value="">Bukan Tabungan Terencana (Transfer Operasional Biasa)</option>
                        <option value="Dana Darurat">Dana Darurat</option>
                        <option value="Tabungan Masa Depan">Tabungan Masa Depan</option>
                        <option value="Investasi">Investasi</option>
                    </select>
                </div>
            </div>
        @endif

        {{-- Detail Mode Fields --}}
        @if($mode === 'detail' && $type !== 'transfer')
            <div class="mt-4 space-y-4 border-t border-current/10 pt-4">
                {{-- Main Category Selector (Maksimal 5 Kategori Utama) --}}
                <div>
                    <label class="block text-xs font-bold text-current/70 mb-1.5">Kategori Utama</label>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                        @foreach($parentCategories as $pCat)
                            <button type="button" wire:click="$set('parent_category_id', '{{ $pCat->id }}')"
                                    class="flex items-center justify-center rounded-2xl border p-2.5 text-center transition-all {{ $parent_category_id === $pCat->id ? 'border-emerald-500 bg-emerald-500/15 font-black text-emerald-800 dark:text-emerald-200 shadow-sm ring-2 ring-emerald-500/20' : 'border-current/10 bg-current/5 hover:bg-current/10 text-current/70' }}">
                                <span class="text-xs truncate">{{ $pCat->name }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Subkategori Manual (Bisa diketik manual atau dipilih dari saran) --}}
                <div>
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-current/70">Sub-Kategori</label>
                        <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">Bisa diketik manual</span>
                    </div>
                    <div class="relative mt-1">
                        <input type="text" list="subcategories-datalist" wire:model.live="subcategory_name"
                               placeholder="Ketik subkategori bebas atau pilih dari daftar..."
                               class="w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" />
                        <datalist id="subcategories-datalist">
                            @foreach($availableSubcategories as $sub)
                                <option value="{{ $sub->name }}">{{ $sub->name }}</option>
                            @endforeach
                        </datalist>
                    </div>
                    <p class="mt-1 text-[11px] text-current/50">Ketik nama subkategori baru secara bebas (otomatis disimpan) atau pilih dari daftar yang ada.</p>
                    @error('category_id') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>

                {{-- Payee / Penerima with Auto Suggestion (US-FIN-01 AC6) --}}
                <div>
                    <label class="block text-xs font-bold text-current/70">Pihak / Toko / Merchant (Opsional)</label>
                    <input type="text" wire:model.live.debounce.400ms="payee" placeholder="mis. Indomaret, PLN, Gaji Kantor"
                           class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" />
                </div>

                @if($suggested_category_name)
                    <div class="flex items-center justify-between rounded-xl bg-emerald-500/10 p-2.5 text-xs text-emerald-800 dark:text-emerald-200">
                        <span>Saran kategori berdasarkan riwayat: <strong>{{ $suggested_category_name }}</strong></span>
                        <button type="button" wire:click="applySuggestion" class="rounded-lg bg-emerald-600 px-2 py-1 text-white font-bold">
                            Terapkan
                        </button>
                    </div>
                @endif

                {{-- Date --}}
                <div>
                    <label class="block text-xs font-bold text-current/70">Tanggal Transaksi</label>
                    <input type="date" wire:model="occurred_on"
                           class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" />
                    @error('occurred_on') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>

                {{-- Note --}}
                <div>
                    <label class="block text-xs font-bold text-current/70">Catatan (Opsional)</label>
                    <input type="text" wire:model="note" placeholder="mis. Belanja sayur mingguan"
                           class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" />
                </div>
            </div>
        @endif

        {{-- Save Button (for detail & transfer modes) --}}
        @if($mode === 'detail' || $type === 'transfer')
            <div class="mt-6 flex items-center justify-end gap-2 border-t border-current/10 pt-4">
                <a href="{{ route('finance.transactions.index') }}" class="rounded-xl px-4 py-2 text-xs font-bold text-current/70 hover:bg-current/10">
                    Batal
                </a>
                <button type="button" wire:click="saveTransaction"
                        class="rounded-xl bg-emerald-600 px-6 py-2.5 text-xs sm:text-sm font-bold text-white shadow-sm hover:bg-emerald-700 active:scale-95">
                    Simpan Transaksi
                </button>
            </div>
        @endif
    </div>
</div>
