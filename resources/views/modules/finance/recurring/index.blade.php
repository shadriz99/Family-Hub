<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-[#263c32] dark:text-[#f3f1e8]">Tagihan & Pemasukan Rutin</h1>
            <p class="text-xs sm:text-sm text-current/60">Otomatis buat rencana bulanan untuk tagihan tetap, langganan, dan gaji berkala</p>
        </div>

        <button wire:click="openCreate"
                class="inline-flex items-center gap-1.5 rounded-2xl bg-[#28543e] px-4 py-2 text-xs sm:text-sm font-bold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-sm hover:opacity-90 active:scale-95 self-start sm:self-auto">
            <x-heroicon-o-plus class="size-4" />
            <span>Tambah Rutinitas</span>
        </button>
    </div>

    @if (session()->has('message'))
        <div class="rounded-2xl bg-[#28543e] dark:bg-[#bad6ae]/10 p-4 text-xs sm:text-sm font-semibold text-[#28543e] dark:text-[#bad6ae]">
            {{ session('message') }}
        </div>
    @endif

    {{-- Bills Grid --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($bills as $bill)
            <div class="flex flex-col justify-between rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-sm transition-colors dark:bg-[#1f2c25] {{ !$bill->is_active ? 'opacity-50' : '' }}">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $bill->direction->value === 'expense' ? 'bg-rose-500/10 text-[#ad3f45]' : 'bg-[#28543e]/10 dark:bg-[#bad6ae]/15 text-[#28543e]' }}">
                            {{ $bill->direction->label() }}
                        </span>

                        <span class="text-xs font-semibold text-current/60">
                            {{ $bill->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>

                    <h3 class="mt-2 text-base font-bold text-[#263c32] dark:text-[#f3f1e8]">{{ $bill->name }}</h3>
                    <div class="mt-1 text-xl font-black tracking-tight text-current/90">
                        {{ $bill->is_variable_amount ? '± ' : '' }}<x-money :amount="$bill->amount" />
                    </div>

                    <div class="mt-3 space-y-1 text-xs text-current/60">
                        <div>Kategori: <strong class="text-current/80">{{ $bill->category->name }}</strong></div>
                        <div>Akun Default: <strong class="text-current/80">{{ $bill->account->name }}</strong></div>
                        <div>Mulai: {{ $bill->dtstart->translatedFormat('d M Y') }}</div>
                        @if($bill->until)
                            <div>Sampai: {{ $bill->until->translatedFormat('d M Y') }}</div>
                        @endif
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-end gap-2 border-t border-current/10 pt-3">
                    <button wire:click="openEdit('{{ $bill->id }}')" class="rounded-xl px-2.5 py-1 text-xs font-semibold text-current/70 hover:bg-current/10">
                        Edit
                    </button>
                    <button wire:click="toggleActive('{{ $bill->id }}')"
                            class="rounded-xl px-2.5 py-1 text-xs font-semibold {{ $bill->is_active ? 'text-[#ad3f45] hover:bg-rose-500/10' : 'text-[#28543e] hover:opacity-90/10' }}">
                        {{ $bill->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-3xl border border-dashed border-current/15 p-12 text-center text-xs text-current/60">
                Belum ada tagihan atau pemasukan rutin terdaftar.
            </div>
        @endforelse
    </div>

    {{-- Form Modal --}}
    @if($showFormModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-3xl border border-current/10 bg-[#fffefa] p-6 shadow-2xl transition-colors dark:bg-[#1f2c25]">
                <h3 class="text-lg font-black text-[#263c32] dark:text-[#f3f1e8]">
                    {{ $editingBillId ? 'Edit Rutinitas' : 'Tambah Tagihan / Pemasukan Rutin' }}
                </h3>

                <form wire:submit.prevent="saveBill" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-current/70">Arah Arus Kas</label>
                        <select wire:model="direction"
                                class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none">
                            <option value="expense">Pengeluaran Rutin (Tagihan / Biaya)</option>
                            <option value="income">Pemasukan Rutin (Gaji / Pendapatan Tetap)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-current/70">Nama Tagihan / Pemasukan</label>
                        <input type="text" wire:model="name" placeholder="mis. Listrik PLN, Tagihan Internet, Gaji Bulanan"
                               class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                        @error('name') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-current/70">Kategori</label>
                            <select wire:model="category_id"
                                    class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none">
                                <option value="">-- Kategori --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-current/70">Akun Default</label>
                            <select wire:model="account_id"
                                    class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none">
                                <option value="">-- Akun --</option>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                                @endforeach
                            </select>
                            @error('account_id') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-current/70">Nominal Estimasi (Rp)</label>
                        <input type="text" wire:model="amount" placeholder="mis. 500rb atau 500000"
                               class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm font-bold focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                        @error('amount') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="is_var" wire:model="is_variable_amount" class="size-4 rounded accent-[#28543e] dark:accent-[#bad6ae]" />
                        <label for="is_var" class="text-xs font-semibold text-current/80">Nominal berubah-ubah tiap bulan (mis. listrik/air)</label>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-current/70">Ulang Tiap Tanggal (1-28)</label>
                            <input type="number" min="1" max="28" wire:model="monthly_day"
                                   class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-current/70">Mulai Tanggal</label>
                            <input type="date" wire:model="dtstart"
                                   class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-current/70">Pengingat Sebelum Jatuh Tempo (Hari)</label>
                        <input type="number" min="0" max="30" wire:model="remind_days_before"
                               class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                    </div>

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
</div>
