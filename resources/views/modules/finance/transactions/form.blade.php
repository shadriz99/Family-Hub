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
                class="flex-1 rounded-xl py-2 text-xs sm:text-sm font-bold transition-all {{ $type === 'expense' ? 'bg-[#ad3f45] dark:bg-[#ffafb1] dark:text-[#151f1b] text-white shadow-sm' : 'text-current/70 hover:bg-current/10' }}">
            Pengeluaran
        </button>
        <button wire:click="$set('type', 'income'); $set('mode', 'quick')"
                class="flex-1 rounded-xl py-2 text-xs sm:text-sm font-bold transition-all {{ $type === 'income' ? 'bg-[#28543e] text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-sm' : 'text-current/70 hover:bg-current/10' }}">
            Pemasukan
        </button>
        <button wire:click="$set('type', 'transfer'); $set('mode', 'transfer')"
                class="flex-1 rounded-xl py-2 text-xs sm:text-sm font-bold transition-all {{ $type === 'transfer' ? 'bg-[#386578] dark:bg-[#a7cad6] dark:text-[#151f1b] text-white shadow-sm' : 'text-current/70 hover:bg-current/10' }}">
            Transfer
        </button>
    </div>

    {{-- Scan Receipt Banner / Trigger (US-FIN-01 AC5) --}}
    @if($type === 'expense')
        <div x-data="{ uploading: false, progress: 0, uploadError: null }"
             x-on:livewire-upload-start="uploading = true; progress = 0; uploadError = null"
             x-on:livewire-upload-finish="uploading = false"
             x-on:livewire-upload-cancel="uploading = false"
             x-on:livewire-upload-error="uploading = false; uploadError = 'Gagal mengunggah foto struk. Pastikan ukuran file wajar dan koneksi stabil.'"
             x-on:livewire-upload-progress="progress = $event.detail.progress"
             class="space-y-2">
            <div class="flex items-center justify-between rounded-2xl border border-[#28543e] dark:border-[#bad6ae]/20 bg-[#28543e] dark:bg-[#bad6ae]/10 p-3.5">
                <div class="flex items-center gap-2.5">
                    <x-heroicon-o-camera class="size-5 text-[#28543e] dark:text-[#bad6ae]" />
                    <div class="text-xs font-semibold text-[#28543e] dark:text-[#bad6ae]">
                        <span>Pindai Struk Belanja (OCR Lokal)</span>
                        <span class="block text-[10px] text-current/70">Otomatis deteksi merchant, tanggal, & total</span>
                    </div>
                </div>

                <div class="flex items-center">
                    {{-- Uploading State: Client to Server Transfer --}}
                    <div x-show="uploading" x-cloak class="flex items-center gap-2 text-xs font-bold text-[#28543e] dark:text-[#bad6ae]">
                        <svg class="h-4 w-4 animate-spin text-[#28543e] dark:text-[#bad6ae]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span x-text="'Mengunggah ' + progress + '%'"></span>
                    </div>

                    {{-- OCR Processing State: Server running Tesseract --}}
                    <div x-show="!uploading" wire:loading wire:target="receipt_file" class="flex items-center gap-2 text-xs font-bold text-[#28543e] dark:text-[#bad6ae]">
                        <svg class="h-4 w-4 animate-spin text-[#28543e] dark:text-[#bad6ae]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Membaca OCR...</span>
                    </div>

                    {{-- Camera / Select Button --}}
                    <div x-show="!uploading">
                        <label wire:loading.remove wire:target="receipt_file" class="cursor-pointer rounded-xl bg-[#28543e] px-3 py-1.5 text-xs font-bold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-sm hover:opacity-90 active:scale-95">
                            <span>Foto Struk</span>
                            <input type="file" wire:model="receipt_file" accept="image/*" capture="environment" class="hidden" />
                        </label>
                    </div>
                </div>
            </div>

            {{-- Upload Progress Bar --}}
            <div x-show="uploading" x-cloak class="overflow-hidden rounded-full bg-[#28543e]/10 dark:bg-[#28543e]/10">
                <div class="h-1.5 rounded-full bg-[#28543e] dark:bg-[#bad6ae] transition-all duration-150 ease-out"
                     :style="'width: ' + progress + '%'"></div>
            </div>

            <div x-show="uploadError" x-cloak class="rounded-2xl border border-rose-500/20 bg-rose-500/10 p-3 text-xs font-semibold text-[#ad3f45] dark:text-rose-300" x-text="uploadError">
            </div>

            @error('receipt_file')
                <div class="rounded-2xl border border-rose-500/20 bg-rose-500/10 p-3 text-xs font-semibold text-[#ad3f45] dark:text-rose-300">
                    {{ $message }}
                </div>
            @enderror

            @if($receipt_ocr_summary)
                <div class="rounded-2xl border border-blue-500/20 bg-blue-500/10 p-3 text-xs font-semibold text-blue-700 dark:text-blue-300">
                    {{ $receipt_ocr_summary }}
                </div>
            @endif
        </div>
    @endif

    {{-- Main Form Card --}}
    <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 sm:p-7 shadow-sm transition-colors dark:bg-[#1f2c25]">
        {{-- Amount Input (Autofocus, supports "45rb", "1,2jt") --}}
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-current/60">Nominal (Rp)</label>
            <div class="relative mt-2">
                <input type="text" inputmode="text" wire:model="amount" autofocus placeholder="0 (mis. 45rb, 1,2jt)"
                       class="w-full rounded-2xl border-2 border-current/20 bg-transparent px-4 py-3 text-2xl sm:text-3xl font-black tracking-tight tabular-nums focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
            </div>
            @error('amount') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
        </div>

        {{-- Account Selector --}}
        <div class="mt-4">
            <label class="block text-xs font-bold text-current/70">
                {{ $type === 'transfer' ? 'Dari Akun Sumber' : 'Menggunakan Akun' }}
            </label>
            <select wire:model="account_id"
                    class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm font-semibold focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none">
                @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->type->label() }})</option>
                @endforeach
            </select>
            @error('account_id') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
        </div>

        {{-- Quick Mode vs Detail Mode Toggle --}}
        @if($type !== 'transfer')
            <div class="mt-5 flex items-center justify-between border-t border-current/10 pt-4">
                <span class="text-xs font-bold text-current/70">
                    {{ $mode === 'quick' ? 'Mode Cepat (1-Tap Simpan)' : 'Mode Rincian Lengkap' }}
                </span>
                <button type="button" wire:click="$set('mode', '{{ $mode === 'quick' ? 'detail' : 'quick' }}')"
                        class="inline-flex items-center gap-1 text-xs font-bold text-[#28543e] hover:text-[#28543e] dark:text-[#bad6ae] dark:hover:opacity-80">
                    <span>{{ $mode === 'quick' ? 'Buka Detail Lengkap' : 'Tutup ke Mode Cepat' }}</span>
                    @if($mode === 'quick')<x-heroicon-o-chevron-down class="size-4" />@else<x-heroicon-o-chevron-up class="size-4" />@endif
                </button>
            </div>

            {{-- Mode Cepat: Top 8 Quick Categories (US-FIN-01 AC1 & AC2) --}}
            @if($mode === 'quick')
                <div class="mt-4">
                    <label class="block text-xs font-semibold text-current/60 mb-2">Tap kategori untuk langsung menyimpan:</label>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        @foreach($quickCategories as $qCat)
                            <button type="button" wire:click="quickSaveCategory('{{ $qCat->id }}')"
                                    class="flex flex-col items-center justify-center rounded-2xl border border-current/10 bg-current/5 p-3 text-center transition-all hover:opacity-90/10 hover:border-[#28543e]/50 dark:hover:border-[#bad6ae]/50 active:scale-95">
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
                            class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm font-semibold focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none">
                        <option value="">-- Pilih Akun Tujuan --</option>
                        @foreach($accounts as $acc)
                            @if($acc->id !== $account_id)
                                <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->type->label() }})</option>
                            @endif
                        @endforeach
                    </select>
                    @error('to_account_id') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-current/70">Biaya Admin Transfer (Opsional)</label>
                    <input type="text" wire:model="admin_fee" placeholder="mis. 2.500 atau 6500"
                           class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                    <p class="text-[11px] text-current/50 mt-0.5">Otomatis dicatat sebagai pengeluaran terpisah (Biaya Admin Bank).</p>
                </div>

                {{-- Savings allocation goal --}}
                <div>
                    <label class="block text-xs font-bold text-current/70">Tujuan Penempatan Dana (Tabungan & Investasi)</label>
                    <select wire:model="allocation_detail"
                            class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none">
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
                {{-- Split Multi-Kategori Toggle (IMP-03) --}}
                @if($type === 'expense')
                    <div class="flex items-center justify-between rounded-2xl border border-current/10 bg-current/5 p-3">
                        <div>
                            <div class="text-xs font-bold text-current">Split Multi-Kategori</div>
                            <div class="text-[11px] text-current/60">Pecah pengeluaran ini ke beberapa kategori berbeda (mis. belanja supermarket).</div>
                        </div>
                        <button type="button" wire:click="toggleSplit"
                                class="rounded-xl px-3 py-1.5 text-xs font-bold transition-all {{ $is_split ? 'bg-[#28543e] text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-sm' : 'border border-current/20 hover:bg-current/10 text-current/70' }}">
                            {{ $is_split ? 'Mode Split Aktif' : 'Aktifkan Split' }}
                        </button>
                    </div>

                    @if($is_split)
                        <div class="space-y-3 rounded-2xl border border-[#28543e] dark:border-[#bad6ae]/20 bg-[#28543e] dark:bg-[#bad6ae]/5 p-3.5">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-[#28543e] dark:text-[#bad6ae]">Rincian Alokasi Kategori (Wajib Sama dengan Total)</span>
                                <button type="button" wire:click="addSplitRow" class="text-xs font-bold text-[#28543e] dark:text-[#bad6ae] hover:underline">
                                    + Tambah Kategori
                                </button>
                            </div>

                            @error('splits')
                                <div class="rounded-xl bg-rose-500/10 border border-rose-500/20 p-2 text-xs font-semibold text-[#ad3f45] dark:text-[#ffafb1]">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="space-y-2.5">
                                @foreach($splits as $index => $splitRow)
                                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 rounded-xl bg-[#fffefa]/70 dark:bg-black/20 p-2 border border-current/10">
                                        <div class="flex-1">
                                            <select wire:model="splits.{{ $index }}.category_id"
                                                    class="w-full rounded-lg border border-current/15 bg-transparent px-2.5 py-1.5 text-xs font-semibold focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none">
                                                <option value="">-- Pilih Kategori --</option>
                                                @foreach($allCategories as $cat)
                                                    <option value="{{ $cat->id }}">{{ $cat->parent ? $cat->parent->name . ' &rsaquo; ' : '' }}{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                            @error("splits.{$index}.category_id") <span class="text-[10px] text-[#ad3f45]">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="w-full sm:w-36">
                                            <input type="text" wire:model.live.debounce.300ms="splits.{{ $index }}.amount" placeholder="Nominal Rp"
                                                   class="w-full rounded-lg border border-current/15 bg-transparent px-2.5 py-1.5 text-xs font-mono font-semibold focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                                            @error("splits.{$index}.amount") <span class="text-[10px] text-[#ad3f45]">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="flex-1">
                                            <input type="text" wire:model="splits.{{ $index }}.notes" placeholder="Catatan (opsional)"
                                                   class="w-full rounded-lg border border-current/15 bg-transparent px-2.5 py-1.5 text-xs focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                                        </div>

                                        @if(count($splits) > 2)
                                            <button type="button" wire:click="removeSplitRow({{ $index }})"
                                                    class="self-center p-1 text-rose-500 hover:text-[#ad3f45] text-sm font-bold"
                                                    title="Hapus baris">
                                                &times;
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            {{-- Real-time Split Balance Summary --}}
                            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-[#28543e] dark:border-[#bad6ae]/20 pt-2.5 text-xs">
                                <div>
                                    <span class="text-current/60">Total Rincian:</span>
                                    <strong class="font-mono ml-1">{{ \App\Core\Support\Money::format($this->splitTotal) }}</strong>
                                </div>

                                <div>
                                    @if($this->splitRemainder === 0 && $this->splitTotal > 0)
                                        <span class="inline-flex items-center gap-1 font-bold text-[#28543e] dark:text-[#bad6ae]">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Alokasi Pas (Sisa Rp 0)
                                        </span>
                                    @elseif($this->splitRemainder > 0)
                                        <span class="font-bold text-[#87571f] dark:text-[#edc48b]">
                                            Belum teralokasi: {{ \App\Core\Support\Money::format($this->splitRemainder) }}
                                        </span>
                                    @else
                                        <span class="font-bold text-[#ad3f45] dark:text-[#ffafb1]">
                                            Kelebihan alokasi: {{ \App\Core\Support\Money::format(abs($this->splitRemainder)) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

                @if(!$is_split)
                    {{-- Main Category Selector (Maksimal 5 Kategori Utama) --}}
                    <div>
                        <label class="block text-xs font-bold text-current/70 mb-1.5">Kategori Utama</label>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                            @foreach($parentCategories as $pCat)
                                <button type="button" wire:click="$set('parent_category_id', '{{ $pCat->id }}')"
                                        class="flex items-center justify-center rounded-2xl border p-2.5 text-center transition-all {{ $parent_category_id === $pCat->id ? 'border-[#28543e] dark:border-[#bad6ae] bg-[#28543e]/15 dark:bg-[#bad6ae]/20 font-black text-[#28543e] dark:text-[#bad6ae] shadow-sm ring-2 ring-[#28543e]/20 dark:ring-[#bad6ae]/25' : 'border-current/10 bg-current/5 hover:bg-current/10 text-current/70' }}">
                                    <span class="text-xs truncate">{{ $pCat->name }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Subkategori Manual (Bisa diketik manual atau dipilih dari saran) --}}
                    <div>
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-current/70">Sub-Kategori</label>
                            <span class="text-[10px] text-[#28543e] dark:text-[#bad6ae] font-semibold">Bisa diketik manual</span>
                        </div>
                        <div class="relative mt-1">
                            <input type="text" list="subcategories-datalist" wire:model.live="subcategory_name"
                                   placeholder="Ketik subkategori bebas atau pilih dari daftar..."
                                   class="w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                            <datalist id="subcategories-datalist">
                                @foreach($availableSubcategories as $sub)
                                    <option value="{{ $sub->name }}">{{ $sub->name }}</option>
                                @endforeach
                            </datalist>
                        </div>
                        <p class="mt-1 text-[11px] text-current/50">Ketik nama subkategori baru secara bebas (otomatis disimpan) atau pilih dari daftar yang ada.</p>
                        @error('category_id') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
                    </div>
                @endif

                {{-- Payee / Penerima with Auto Suggestion (US-FIN-01 AC6) --}}
                <div>
                    <label class="block text-xs font-bold text-current/70">Pihak / Toko / Merchant (Opsional)</label>
                    <input type="text" wire:model.live.debounce.400ms="payee" placeholder="mis. Indomaret, PLN, Gaji Kantor"
                           class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                </div>

                @if($suggested_category_name)
                    <div class="flex items-center justify-between rounded-xl bg-[#28543e] dark:bg-[#bad6ae]/10 p-2.5 text-xs text-[#28543e] dark:text-[#bad6ae]">
                        <span>Saran kategori berdasarkan riwayat: <strong>{{ $suggested_category_name }}</strong></span>
                        <button type="button" wire:click="applySuggestion" class="rounded-lg bg-[#28543e] px-2 py-1 text-white dark:bg-[#bad6ae] dark:text-[#151f1b] font-bold">
                            Terapkan
                        </button>
                    </div>
                @endif

                {{-- Date --}}
                <div>
                    <label class="block text-xs font-bold text-current/70">Tanggal Transaksi</label>
                    <input type="date" wire:model="occurred_on"
                           class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                    @error('occurred_on') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
                </div>

                {{-- Note --}}
                <div>
                    <label class="block text-xs font-bold text-current/70">Catatan (Opsional)</label>
                    <input type="text" wire:model="note" placeholder="mis. Belanja sayur mingguan"
                           class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
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
                        class="rounded-xl bg-[#28543e] px-6 py-2.5 text-xs sm:text-sm font-bold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-sm hover:opacity-90 active:scale-95">
                    Simpan Transaksi
                </button>
            </div>
        @endif
    </div>
</div>
