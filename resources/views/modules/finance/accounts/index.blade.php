<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-[#263c32] dark:text-[#f3f1e8]">Akun & Dompet</h1>
            <p class="text-xs sm:text-sm text-current/60">Kelola rekening bank, dompet digital, kartu kredit, dan tabungan keluarga</p>
        </div>

        <div class="flex items-center gap-2">
            <button wire:click="openReconcile"
                    class="inline-flex items-center gap-1.5 rounded-2xl border border-current/15 bg-current/5 px-3.5 py-2 text-xs sm:text-sm font-bold text-current transition-all hover:bg-current/10 active:scale-95">
                <x-heroicon-o-scale class="size-4" />
                <span>Rekonsiliasi Saldo</span>
            </button>

            <button wire:click="openCreate"
                    class="inline-flex items-center gap-1.5 rounded-2xl bg-[#28543e] px-4 py-2 text-xs sm:text-sm font-bold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-sm transition-all hover:opacity-90 active:scale-95">
                <x-heroicon-o-plus class="size-4" />
                <span>Tambah Akun</span>
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="rounded-2xl bg-[#28543e] dark:bg-[#bad6ae]/10 p-4 text-xs sm:text-sm font-semibold text-[#28543e] dark:text-[#bad6ae]">
            {{ session('message') }}
        </div>
    @endif

    {{-- Active Accounts Grid --}}
    <div>
        <h2 class="mb-3 text-sm font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">Akun Aktif</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($activeAccounts as $item)
                @php
                    $acc = $item['account'];
                    $bal = $item['balance'];
                @endphp
                <div class="flex flex-col justify-between rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-sm transition-colors dark:bg-[#1f2c25]">
                    <div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="size-3.5 rounded-full" style="background-color: {{ $acc->color }}"></span>
                                <span class="text-xs font-bold uppercase tracking-wider text-current/50">{{ $acc->type->label() }}</span>
                            </div>
                            @if(!$acc->include_in_total)
                                <span class="rounded-full bg-current/10 px-2 py-0.5 text-[10px] font-semibold text-current/60">Di luar total</span>
                            @endif
                        </div>

                        <h3 class="mt-2 text-base font-bold text-[#263c32] dark:text-[#f3f1e8]">{{ $acc->name }}</h3>
                        <div class="mt-1 text-2xl font-black tracking-tight {{ $bal < 0 ? 'text-[#ad3f45] dark:text-[#ffafb1]' : 'text-current/90' }}">
                            <x-money :amount="$bal" />
                        </div>

                        @if($acc->type->value === 'credit_card' && $acc->credit_limit)
                            <div class="mt-2 text-xs text-current/60">
                                Limit: <x-money :amount="$acc->credit_limit" />
                            </div>
                        @endif
                    </div>

                    <div class="mt-5 flex items-center justify-end gap-2 border-t border-current/10 pt-3">
                        <button wire:click="openAdjust('{{ $acc->id }}')" class="rounded-xl px-2.5 py-1 text-xs font-semibold text-current/70 hover:bg-current/10">
                            Sesuaikan Saldo
                        </button>
                        <button wire:click="openEdit('{{ $acc->id }}')" class="rounded-xl px-2.5 py-1 text-xs font-semibold text-current/70 hover:bg-current/10">
                            Edit
                        </button>
                        <button wire:click="toggleArchive('{{ $acc->id }}')" class="rounded-xl px-2.5 py-1 text-xs font-semibold text-[#ad3f45] hover:bg-rose-500/10 dark:text-[#ffafb1]">
                            Arsipkan
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-current/15 p-8 text-center text-xs text-current/60">
                    Belum ada akun aktif. Klik "Tambah Akun" untuk membuat akun pertama.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Archived Accounts --}}
    @if(count($archivedAccounts) > 0)
        <div class="mt-8 border-t border-current/10 pt-6">
            <h2 class="mb-3 text-sm font-bold tracking-tight text-current/60">Akun Diarsipkan</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($archivedAccounts as $item)
                    @php
                        $acc = $item['account'];
                        $bal = $item['balance'];
                    @endphp
                    <div class="flex items-center justify-between rounded-2xl border border-dashed border-current/15 bg-current/5 p-4 opacity-70">
                        <div>
                            <div class="text-sm font-bold text-current/80 line-through">{{ $acc->name }}</div>
                            <div class="text-xs text-current/50"><x-money :amount="$bal" /></div>
                        </div>
                        <button wire:click="toggleArchive('{{ $acc->id }}')" class="rounded-xl bg-current/10 px-3 py-1.5 text-xs font-semibold text-current/80 hover:bg-current/20">
                            Aktifkan Lagi
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Create / Edit Modal --}}
    @if($showCreateModal || $showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-3xl border border-current/10 bg-[#fffefa] p-6 shadow-2xl transition-colors dark:bg-[#1f2c25]">
                <h3 class="text-lg font-black text-[#263c32] dark:text-[#f3f1e8]">
                    {{ $showEditModal ? 'Edit Akun' : 'Tambah Akun Baru' }}
                </h3>

                <form wire:submit.prevent="saveAccount" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-current/70">Nama Akun / Bank</label>
                        <input type="text" wire:model="name" placeholder="mis. BCA Utama, Dompet Tunai"
                               class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                        @error('name') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
                    </div>

                    @if(!$showEditModal)
                        <div>
                            <label class="block text-xs font-bold text-current/70">Tipe Akun</label>
                            <select wire:model.live="type"
                                    class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none">
                                <option value="cash">Uang Tunai (Cash)</option>
                                <option value="bank">Rekening Bank</option>
                                <option value="ewallet">Dompet Digital (E-Wallet)</option>
                                <option value="credit_card">Kartu Kredit</option>
                                <option value="savings">Tabungan / Investasi</option>
                            </select>
                            @error('type') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-current/70">
                                Saldo Awal {{ $type === 'credit_card' ? '(Tagihan Berjalan, masukkan negatif mis. -1500000)' : '' }}
                            </label>
                            <input type="text" wire:model="opening_balance" placeholder="0"
                                   class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                            @error('opening_balance') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-current/70">Tanggal Saldo Awal</label>
                            <input type="date" wire:model="opening_date"
                                   class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                            @error('opening_date') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    @if($type === 'credit_card')
                        <div>
                            <label class="block text-xs font-bold text-current/70">Limit Kartu Kredit (Opsional)</label>
                            <input type="text" wire:model="credit_limit" placeholder="mis. 15000000"
                                   class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                            @error('credit_limit') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-current/70">Warna Aksen</label>
                            <input type="color" wire:model="color" class="mt-1 h-9 w-full rounded-xl border border-current/20 bg-transparent cursor-pointer p-1" />
                            @error('color') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-current/70">Ikon Heroicon</label>
                            <input type="text" wire:model="icon" placeholder="wallet, banknotes, etc."
                                   class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" id="include_in_total" wire:model="include_in_total" class="size-4 rounded accent-[#28543e] dark:accent-[#bad6ae]" />
                        <label for="include_in_total" class="text-xs font-semibold text-current/80">Hitung dalam total saldo utama</label>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showCreateModal', false); $set('showEditModal', false)"
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

    {{-- Adjust Balance Modal --}}
    @if($showAdjustModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-3xl border border-current/10 bg-[#fffefa] p-6 shadow-2xl transition-colors dark:bg-[#1f2c25]">
                <h3 class="text-lg font-black text-[#263c32] dark:text-[#f3f1e8]">Sesuaikan Saldo Nyata</h3>
                <p class="mt-1 text-xs text-current/60">
                    Sistem akan otomatis mencatat transaksi penyesuaian saldo (Penyesuaian Saldo) sebesar selisih antara saldo terhitung dan saldo nyata.
                </p>

                <form wire:submit.prevent="saveAdjustment" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-current/70">Saldo Nyata Saat Ini (Rp)</label>
                        <input type="text" wire:model="adjustTargetBalance" placeholder="mis. 5.250.000 atau 5,25jt"
                               class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm font-bold focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-current/70">Tanggal Efektif</label>
                        <input type="date" wire:model="adjustDate"
                               class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-current/70">Catatan (Opsional)</label>
                        <input type="text" wire:model="adjustNote" placeholder="mis. Selisih bunga bank / admin"
                               class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showAdjustModal', false)"
                                class="rounded-xl px-4 py-2 text-xs font-bold text-current/70 hover:bg-current/10">
                            Batal
                        </button>
                        <button type="submit"
                                class="rounded-xl bg-[#28543e] px-5 py-2 text-xs font-bold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-sm hover:opacity-90">
                            Terapkan Penyesuaian
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Reconcile Accounts Modal --}}
    @if($showReconcileModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-lg rounded-3xl border border-current/10 bg-[#fffefa] p-6 shadow-2xl transition-colors dark:bg-[#1f2c25]">
                <h3 class="text-lg font-black text-[#263c32] dark:text-[#f3f1e8]">Rekonsiliasi Saldo Akhir Periode</h3>
                <p class="mt-1 text-xs text-current/60">
                    Masukkan saldo rekening aktual Anda per tanggal akhir periode untuk mencocokkan saldo catatan dengan kenyataan.
                </p>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-current/70">Tanggal Rekonsiliasi</label>
                        <input type="date" wire:model="reconcileDate"
                               class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                    </div>

                    <div class="max-h-60 space-y-3 overflow-y-auto pr-1">
                        @foreach($activeAccounts as $item)
                            @php $acc = $item['account']; @endphp
                            <div class="rounded-2xl border border-current/10 bg-current/5 p-3">
                                <div class="flex items-center justify-between text-xs font-bold">
                                    <span>{{ $acc->name }}</span>
                                    <span class="text-current/60">Terhitung: <x-money :amount="$item['balance']" /></span>
                                </div>
                                <div class="mt-2">
                                    <input type="text" wire:model="reconcileBalances.{{ $acc->id }}" placeholder="Saldo riil"
                                           class="w-full rounded-xl border border-current/20 bg-transparent px-3 py-1.5 text-xs font-semibold focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($reconcilePreviewReady)
                        <div class="rounded-2xl border border-amber-500/20 bg-amber-500/10 p-3 text-xs">
                            <h4 class="font-bold text-amber-800 dark:text-amber-200">Pratinjau Selisih:</h4>
                            <div class="mt-2 space-y-1">
                                @foreach($reconcilePreview as $p)
                                    <div class="flex justify-between">
                                        <span>{{ $p['account']->name }}:</span>
                                        <span class="font-bold {{ $p['diff'] === 0 ? 'text-current/60' : ($p['diff'] > 0 ? 'text-[#28543e]' : 'text-[#ad3f45]') }}">
                                            {{ $p['diff'] > 0 ? '+' : '' }}<x-money :amount="$p['diff']" />
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="mt-6 flex items-center justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showReconcileModal', false)"
                                class="rounded-xl px-4 py-2 text-xs font-bold text-current/70 hover:bg-current/10">
                            Batal
                        </button>
                        @if(!$reconcilePreviewReady)
                            <button type="button" wire:click="generateReconcilePreview"
                                    class="rounded-xl bg-[#386578] dark:bg-[#a7cad6] dark:text-[#151f1b] px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-blue-700">
                                Pratinjau Selisih
                            </button>
                        @else
                            <button type="button" wire:click="confirmReconcile"
                                    class="rounded-xl bg-[#28543e] px-5 py-2 text-xs font-bold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-sm hover:opacity-90">
                                Konfirmasi & Terapkan
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
