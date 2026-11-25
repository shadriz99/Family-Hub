<div class="space-y-6">
    {{-- Header & Period Switcher --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-[#263c32] dark:text-[#f3f1e8]">Rencana Bulanan</h1>
            <p class="text-xs sm:text-sm text-current/60">Arsitektur rencana keuangan, kewajiban jatuh tempo, dan alokasi periode</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-period-switcher
                :current="$period->start->translatedFormat('d M') . ' - ' . $period->end->translatedFormat('d M Y')"
                :prevUrl="route('finance.plan.index', ['period' => $prevPeriod->key])"
                :nextUrl="route('finance.plan.index', ['period' => $nextPeriod->key])"
            />

            <button wire:click="openManualModal"
                    class="inline-flex items-center gap-1.5 rounded-2xl bg-[#28543e] px-3.5 py-2 text-xs sm:text-sm font-bold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-sm hover:opacity-90 active:scale-95">
                <x-heroicon-o-plus class="size-4" />
                <span>Tambah Rencana</span>
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="rounded-2xl bg-[#28543e] dark:bg-[#bad6ae]/10 p-4 text-xs sm:text-sm font-semibold text-[#28543e] dark:text-[#bad6ae]">
            {{ session('message') }}
        </div>
    @endif

    {{-- 6 Summary Cards (US-FIN-07 AC2) --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <span class="text-[11px] font-semibold text-current/60">Rencana Masuk</span>
            <div class="mt-1 text-base sm:text-lg font-black tracking-tight text-[#28543e] dark:text-[#bad6ae]">
                <x-money :amount="$planIncomeTotal" />
            </div>
        </div>

        <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <span class="text-[11px] font-semibold text-current/60">Rencana Keluar</span>
            <div class="mt-1 text-base sm:text-lg font-black tracking-tight text-[#ad3f45] dark:text-[#ffafb1]">
                <x-money :amount="$planExpenseTotal" />
            </div>
            <span class="text-[10px] text-current/40">Kewajiban + Anggaran</span>
        </div>

        <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <span class="text-[11px] font-semibold text-current/60">Sisa Rencana</span>
            <div class="mt-1 text-base sm:text-lg font-black tracking-tight {{ $remainingPlan < 0 ? 'text-[#ad3f45]' : 'text-[#263c32] dark:text-[#f3f1e8]' }}">
                <x-money :amount="$remainingPlan" />
            </div>
            <span class="text-[10px] text-current/40">Surplus / Defisit</span>
        </div>

        <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <span class="text-[11px] font-semibold text-current/60">Realisasi Masuk</span>
            <div class="mt-1 text-base sm:text-lg font-black tracking-tight text-[#28543e] dark:text-[#bad6ae]">
                <x-money :amount="$realizedIncome" />
            </div>
        </div>

        <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <span class="text-[11px] font-semibold text-current/60">Realisasi Keluar</span>
            <div class="mt-1 text-base sm:text-lg font-black tracking-tight text-[#ad3f45] dark:text-[#ffafb1]">
                <x-money :amount="$realizedExpense" />
            </div>
        </div>

        <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <span class="text-[11px] font-semibold text-current/60">Kewajiban Belum Bayar</span>
            <div class="mt-1 text-base sm:text-lg font-black tracking-tight text-[#87571f] dark:text-[#edc48b]">
                <x-money :amount="$unpaidObligationsAmount" />
            </div>
            <span class="text-[10px] text-current/40">{{ $unpaidObligationsCount }} item belum lunas</span>
        </div>
    </div>

    {{-- 5 Allocations Overview Widget (US-FIN-04A) --}}
    <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 sm:p-6 shadow-sm transition-colors dark:bg-[#1f2c25]">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-current/10 pb-4">
            <div>
                <h2 class="text-base font-bold text-[#263c32] dark:text-[#f3f1e8]">Alokasi Dana Periode (5 Pos)</h2>
                <p class="text-xs text-current/60">
                    Dasar Penghasilan: <strong class="text-current/90"><x-money :amount="$allocationSummary['income_base']" /></strong>
                    @if($allocationSummary['unallocated_target'] > 0)
                        &bull; Belum dialokasikan: <span class="font-bold text-[#87571f]"><x-money :amount="$allocationSummary['unallocated_target']" /></span>
                    @endif
                </p>
            </div>

            <div class="flex items-center gap-2">
                @if($allocationSummary['debt_ratio_warning'])
                    <span class="rounded-full bg-rose-500/20 px-3 py-1 text-xs font-bold text-[#ad3f45] dark:text-rose-300">
                        Peringatan Cicilan: {{ $allocationSummary['debt_ratio'] }}% (>30%)
                    </span>
                @elseif($allocationSummary['debt_ratio'] !== null)
                    <span class="rounded-full bg-[#28543e] dark:bg-[#bad6ae]/10 px-3 py-1 text-xs font-semibold text-[#28543e] dark:text-[#bad6ae]">
                        Rasio Cicilan: {{ $allocationSummary['debt_ratio'] }}%
                    </span>
                @endif

                <button wire:click="openAllocationModal" class="rounded-xl border border-current/15 bg-current/5 px-3 py-1.5 text-xs font-bold hover:bg-current/10">
                    Atur Persentase
                </button>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-5">
            @foreach($allocationSummary['allocations'] as $alloc)
                <div class="rounded-2xl border border-current/10 bg-current/5 p-3.5">
                    <div class="flex items-center justify-between text-xs font-semibold">
                        <span class="text-current/70 truncate max-w-[100px]">{{ $alloc['label'] }}</span>
                        <span class="font-bold">{{ $alloc['percentage'] }}%</span>
                    </div>
                    <div class="mt-2 text-sm font-black text-[#263c32] dark:text-[#f3f1e8]">
                        <x-money :amount="$alloc['target']" />
                    </div>
                    <div class="mt-1 flex items-center justify-between text-[11px] text-current/60">
                        <span>Pakai: <x-money :amount="$alloc['used']" /></span>
                        <span class="{{ $alloc['remaining'] < 0 ? 'text-[#ad3f45] font-bold' : '' }}">
                            {{ $alloc['remaining'] < 0 ? 'Lebih' : 'Sisa' }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Fixed Obligations Section (US-FIN-07 AC3) --}}
    <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 sm:p-6 shadow-sm transition-colors dark:bg-[#1f2c25]">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-[#263c32] dark:text-[#f3f1e8]">Kewajiban Tetap</h2>
                <p class="text-xs text-current/60">Cicilan pinjaman, tagihan rutin, & rencana pengeluaran bulan ini</p>
            </div>
        </div>

        <div class="space-y-4">
            {{-- 1. Overdue Items --}}
            @if($overdueItems->isNotEmpty())
                <div>
                    <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-[#ad3f45] dark:text-[#ffafb1]">
                        Terlambat ({{ $overdueItems->count() }})
                    </h3>
                    <div class="space-y-2">
                        @foreach($overdueItems as $item)
                            @include('finance::plan._obligation_row', ['item' => $item, 'badge' => 'Terlambat', 'badgeColor' => 'bg-rose-500/20 text-[#ad3f45]'])
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- 2. Unpaid Items (ordered by due date) --}}
            @if($unpaidItems->isNotEmpty())
                <div>
                    <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-current/60">
                        Belum Dibayar ({{ $unpaidItems->count() }})
                    </h3>
                    <div class="space-y-2">
                        @foreach($unpaidItems as $item)
                            @include('finance::plan._obligation_row', ['item' => $item, 'badge' => 'Rencana', 'badgeColor' => 'bg-blue-500/10 text-blue-600'])
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- 3. Partial Items --}}
            @if($partialItems->isNotEmpty())
                <div>
                    <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-[#87571f] dark:text-[#edc48b]">
                        Sebagian Dibayar ({{ $partialItems->count() }})
                    </h3>
                    <div class="space-y-2">
                        @foreach($partialItems as $item)
                            @include('finance::plan._obligation_row', ['item' => $item, 'badge' => 'Sebagian', 'badgeColor' => 'bg-amber-500/20 text-[#87571f]'])
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- 4. Paid Items --}}
            @if($paidItems->isNotEmpty())
                <div>
                    <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-[#28543e] dark:text-[#bad6ae]">
                        Lunas ({{ $paidItems->count() }})
                    </h3>
                    <div class="space-y-2">
                        @foreach($paidItems as $item)
                            @include('finance::plan._obligation_row', ['item' => $item, 'badge' => 'Lunas', 'badgeColor' => 'bg-[#28543e] dark:bg-[#bad6ae]/20 text-[#28543e]'])
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- 5. Skipped Items --}}
            @if($skippedItems->isNotEmpty())
                <div>
                    <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-current/40">
                        Dilewati / Dibatalkan ({{ $skippedItems->count() }})
                    </h3>
                    <div class="space-y-2">
                        @foreach($skippedItems as $item)
                            @include('finance::plan._obligation_row', ['item' => $item, 'badge' => 'Dilewati', 'badgeColor' => 'bg-current/10 text-current/50'])
                        @endforeach
                    </div>
                </div>
            @endif

            @if($expenseItems->isEmpty())
                <div class="rounded-2xl border border-dashed border-current/15 p-8 text-center text-xs text-current/60">
                    Belum ada kewajiban tetap tercatat pada periode ini.
                </div>
            @endif
        </div>
    </div>

    {{-- Pay Modal --}}
    @if($showPayModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-3xl border border-current/10 bg-[#fffefa] p-6 shadow-2xl transition-colors dark:bg-[#1f2c25]">
                <h3 class="text-lg font-black text-[#263c32] dark:text-[#f3f1e8]">Bayar Kewajiban</h3>
                <p class="mt-1 text-xs text-current/60">{{ $payItemName }}</p>

                <form wire:submit.prevent="executePayment" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-current/70">Nominal Pembayaran (Rp)</label>
                        <input type="text" wire:model="payAmount"
                               class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-base font-bold focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                        @error('payAmount') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-current/70">Bayar Menggunakan Akun</label>
                        <select wire:model="payAccountId"
                                class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none">
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->type->label() }})</option>
                            @endforeach
                        </select>
                        @error('payAccountId') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-current/70">Tanggal Bayar</label>
                        <input type="date" wire:model="payDate"
                               class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-current/70">Catatan (Opsional)</label>
                        <input type="text" wire:model="payNote" placeholder="mis. No ref / bukti transfer"
                               class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showPayModal', false)"
                                class="rounded-xl px-4 py-2 text-xs font-bold text-current/70 hover:bg-current/10">
                            Batal
                        </button>
                        <button type="submit"
                                class="rounded-xl bg-[#28543e] px-5 py-2 text-xs font-bold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-sm hover:opacity-90">
                            Konfirmasi Bayar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Skip Modal --}}
    @if($showSkipModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-3xl border border-current/10 bg-[#fffefa] p-6 shadow-2xl transition-colors dark:bg-[#1f2c25]">
                <h3 class="text-lg font-black text-[#263c32] dark:text-[#f3f1e8]">Lewati Kewajiban Ini</h3>
                <p class="mt-1 text-xs text-current/60">Sertakan alasan mengapa pembayaran ini dilewati pada periode berjalan.</p>

                <form wire:submit.prevent="executeSkip" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-current/70">Alasan Melewati (Wajib min 3 karakter)</label>
                        <input type="text" wire:model="skipReason" placeholder="mis. Ditunda ke bulan depan, Bebas bayar promo"
                               class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                        @error('skipReason') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showSkipModal', false)"
                                class="rounded-xl px-4 py-2 text-xs font-bold text-current/70 hover:bg-current/10">
                            Batal
                        </button>
                        <button type="submit"
                                class="rounded-xl bg-amber-600 px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-amber-700">
                            Lewati
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Manual Planned Item Modal --}}
    @if($showManualModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-3xl border border-current/10 bg-[#fffefa] p-6 shadow-2xl transition-colors dark:bg-[#1f2c25]">
                <h3 class="text-lg font-black text-[#263c32] dark:text-[#f3f1e8]">
                    {{ $manualItemId ? 'Edit Rencana' : 'Tambah Rencana Pengeluaran Sekali' }}
                </h3>

                <form wire:submit.prevent="saveManualItem" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-current/70">Nama Rencana</label>
                        <input type="text" wire:model="manualName" placeholder="mis. Beli Kasur Baru, Servis AC"
                               class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                        @error('manualName') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-current/70">Kategori</label>
                        <select wire:model="manualCategoryId"
                                class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('manualCategoryId') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-current/70">Estimasi Nominal (Rp)</label>
                        <input type="text" wire:model="manualAmount" placeholder="mis. 1,5jt atau 1500000"
                               class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm font-bold focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                        @error('manualAmount') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-current/70">Target Tanggal Jatuh Tempo</label>
                        <input type="date" wire:model="manualDueDate"
                               class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showManualModal', false)"
                                class="rounded-xl px-4 py-2 text-xs font-bold text-current/70 hover:bg-current/10">
                            Batal
                        </button>
                        <button type="submit"
                                class="rounded-xl bg-[#28543e] px-5 py-2 text-xs font-bold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-sm hover:opacity-90">
                            {{ $manualItemId ? 'Simpan Perubahan' : 'Simpan Rencana' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Allocation Plan Modal --}}
    @if($showAllocationModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-3xl border border-current/10 bg-[#fffefa] p-6 shadow-2xl transition-colors dark:bg-[#1f2c25]">
                <h3 class="text-lg font-black text-[#263c32] dark:text-[#f3f1e8]">Konfigurasi 5 Alokasi Dana</h3>
                <p class="mt-1 text-xs text-current/60">Atur dasar penghasilan dan persentase pembagian (maks total 100%).</p>

                <form wire:submit.prevent="saveAllocationPlan" class="mt-4 space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-current/70">Dasar Penghasilan Periode (Rp)</label>
                        <input type="text" wire:model="allocIncomeBase" placeholder="mis. 15.000.000 atau 15jt"
                               class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm font-bold focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <div>
                            <label class="block text-xs font-bold text-current/70">Kebutuhan Hidup (%)</label>
                            <input type="number" step="0.01" wire:model="allocLivingPct"
                                   class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-1.5 text-xs font-bold focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-current/70">Tersier (%)</label>
                            <input type="number" step="0.01" wire:model="allocTertiaryPct"
                                   class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-1.5 text-xs font-bold focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-current/70">Cicilan / Utang (%)</label>
                            <input type="number" step="0.01" wire:model="allocDebtPct"
                                   class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-1.5 text-xs font-bold focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-current/70">Tabungan (%)</label>
                            <input type="number" step="0.01" wire:model="allocSavingsPct"
                                   class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-1.5 text-xs font-bold focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-current/70">Donasi / Sosial / Zakat (%)</label>
                            <input type="number" step="0.01" wire:model="allocSocialPct"
                                   class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-1.5 text-xs font-bold focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                        </div>
                    </div>
                    @error('allocLivingPct') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror

                    <div class="mt-6 flex items-center justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showAllocationModal', false)"
                                class="rounded-xl px-4 py-2 text-xs font-bold text-current/70 hover:bg-current/10">
                            Batal
                        </button>
                        <button type="submit"
                                class="rounded-xl bg-[#28543e] px-5 py-2 text-xs font-bold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-sm hover:opacity-90">
                            Simpan Alokasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
