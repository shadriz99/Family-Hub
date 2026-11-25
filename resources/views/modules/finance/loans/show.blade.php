<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $loan->direction->value === 'payable' ? 'bg-rose-500/10 text-[#ad3f45]' : 'bg-[#28543e]/10 dark:bg-[#bad6ae]/15 text-[#28543e]' }}">
                    {{ $loan->direction->label() }}
                </span>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $loan->status->value === 'paid_off' ? 'bg-blue-500/10 text-blue-600' : 'bg-[#28543e]/10 dark:bg-[#bad6ae]/15 text-[#28543e]' }}">
                    {{ $loan->status->label() }}
                </span>
            </div>
            <h1 class="mt-1 text-xl sm:text-2xl font-black tracking-tight text-[#263c32] dark:text-[#f3f1e8]">{{ $loan->name }}</h1>
            <p class="text-xs sm:text-sm text-current/60">Pihak: <span class="font-bold text-current/80">{{ $loan->counterparty }}</span> &bull; Kategori: {{ $loan->category->name }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($loan->status->value === 'active')
                <button wire:click="openAcceleratedModal"
                        class="inline-flex items-center gap-1.5 rounded-2xl bg-amber-600 px-3.5 py-2 text-xs sm:text-sm font-bold text-white shadow-sm hover:bg-amber-700 active:scale-95">
                    <x-heroicon-o-bolt class="size-4" />
                    <span>Pelunasan Dipercepat</span>
                </button>
            @endif

            <a href="{{ route('finance.loans.edit', $loan->id) }}"
               class="inline-flex items-center gap-1.5 rounded-2xl border border-current/15 bg-current/5 px-3.5 py-2 text-xs sm:text-sm font-bold hover:bg-current/10">
                <x-heroicon-o-pencil class="size-4" />
                <span>Edit Pinjaman</span>
            </a>

            <a href="{{ route('finance.loans.index') }}" class="text-xs font-bold text-current/60 hover:underline px-2">
                &larr; Kembali
            </a>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="rounded-2xl bg-[#28543e] dark:bg-[#bad6ae]/10 p-4 text-xs sm:text-sm font-semibold text-[#28543e] dark:text-[#bad6ae]">
            {{ session('message') }}
        </div>
    @endif

    {{-- Metric Cards (US-FIN-06 AC4) --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <span class="text-[11px] font-semibold text-current/60">Pokok Pinjaman</span>
            <div class="mt-1 text-base font-bold text-[#263c32] dark:text-[#f3f1e8]"><x-money :amount="$loan->principal" /></div>
        </div>

        <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <span class="text-[11px] font-semibold text-current/60">Total Rencana Bayar</span>
            <div class="mt-1 text-base font-bold text-current/80"><x-money :amount="$loan->total_payable" /></div>
        </div>

        <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <span class="text-[11px] font-semibold text-current/60">Selisih Total (Selisih Biaya)</span>
            <div class="mt-1 text-base font-bold text-[#87571f] dark:text-[#edc48b]"><x-money :amount="$loan->totalDifference()" /></div>
        </div>

        <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <span class="text-[11px] font-semibold text-current/60">Total Sudah Dibayar</span>
            <div class="mt-1 text-base font-bold text-[#28543e] dark:text-[#bad6ae]"><x-money :amount="$paidAmount" /></div>
        </div>

        <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <span class="text-[11px] font-semibold text-current/60">Sisa Pinjaman</span>
            <div class="mt-1 text-base font-black text-[#ad3f45] dark:text-[#ffafb1]"><x-money :amount="$remainingAmount" /></div>
        </div>

        <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <span class="text-[11px] font-semibold text-current/60">Perkiraan Lunas</span>
            <div class="mt-1 text-base font-bold text-[#263c32] dark:text-[#f3f1e8]">
                {{ $estimatedSettlementDate ? $estimatedSettlementDate->translatedFormat('M Y') : '-' }}
            </div>
        </div>
    </div>

    {{-- Progress --}}
    <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-sm transition-colors dark:bg-[#1f2c25]">
        <div class="flex items-center justify-between text-xs font-semibold text-current/60 mb-2">
            <span>Progres Pelunasan: {{ $paidCount }} dari {{ $totalCount }} cicilan</span>
            <span class="font-bold">{{ $totalCount > 0 ? round(($paidCount / $totalCount) * 100) : 0 }}%</span>
        </div>
        <div class="h-2.5 w-full overflow-hidden rounded-full bg-current/10">
            <div class="h-full rounded-full bg-[#28543e] dark:bg-[#bad6ae] transition-all" style="width: {{ $totalCount > 0 ? min(100, round(($paidCount / $totalCount) * 100)) : 0 }}%"></div>
        </div>
        @if($loan->interest_note)
            <div class="mt-3 text-xs text-current/60">Catatan Bunga: <span class="italic text-current/80">{{ $loan->interest_note }}</span></div>
        @endif
    </div>

    {{-- Schedule Table (US-FIN-06 AC4) --}}
    <div class="rounded-3xl border border-current/10 bg-[#fffefa] overflow-hidden shadow-sm transition-colors dark:bg-[#1f2c25]">
        <div class="border-b border-current/10 bg-current/5 px-5 py-3">
            <h3 class="text-sm font-bold text-[#263c32] dark:text-[#f3f1e8]">Tabel Seluruh Jadwal Cicilan</h3>
        </div>

        <div class="divide-y divide-current/5">
            @foreach($installments as $item)
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 gap-2">
                    <div class="flex items-center gap-3">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-current/10 font-bold text-xs text-current/70">
                            {{ $item->sequence }}
                        </span>
                        <div>
                            <div class="text-sm font-bold text-[#263c32] dark:text-[#f3f1e8]">
                                {{ $item->name }}
                            </div>
                            <div class="text-xs text-current/60">
                                Jatuh tempo: {{ $item->due_date->translatedFormat('d M Y') }}
                                @if($item->paid_before_tracking)
                                    <span class="ml-1 text-[10px] text-[#28543e] font-semibold">(Dibayar sebelum pencatatan)</span>
                                @endif
                                @if($item->status_reason)
                                    <span class="ml-1 text-[10px] italic text-current/50">&bull; {{ $item->status_reason }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between sm:justify-end gap-4 self-end sm:self-auto">
                        <div class="text-right">
                            <div class="text-sm font-black {{ $item->status->value === 'paid' ? 'text-[#28543e]' : 'text-[#ad3f45]' }}">
                                <x-money :amount="$item->amount" />
                            </div>
                            <span class="rounded px-1.5 py-0.2 text-[10px] font-bold {{ $item->status->value === 'paid' ? 'bg-[#28543e]/10 dark:bg-[#bad6ae]/15 text-[#28543e]' : ($item->status->value === 'partial' ? 'bg-amber-500/10 text-[#87571f]' : ($item->status->value === 'cancelled' ? 'bg-current/10 text-current/50' : 'bg-blue-500/10 text-blue-600')) }}">
                                {{ $item->status->label() }}
                            </span>
                        </div>

                        @if(in_array($item->status->value, ['planned', 'partial']))
                            <button wire:click="openPay('{{ $item->id }}')"
                                    class="rounded-xl bg-[#28543e] px-3 py-1.5 text-xs font-bold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-sm hover:opacity-90 active:scale-95">
                                Bayar
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Installment Pay Modal --}}
    @if($showPayModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-3xl border border-current/10 bg-[#fffefa] p-6 shadow-2xl transition-colors dark:bg-[#1f2c25]">
                <h3 class="text-lg font-black text-[#263c32] dark:text-[#f3f1e8]">Bayar Cicilan</h3>
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
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-current/70">Tanggal Bayar</label>
                        <input type="date" wire:model="payDate"
                               class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-current/70">Catatan (Opsional)</label>
                        <input type="text" wire:model="payNote" placeholder="mis. Bukti transfer ref"
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

    {{-- Accelerated Early Settlement Modal (US-FIN-06 AC6) --}}
    @if($showAcceleratedModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-3xl border border-current/10 bg-[#fffefa] p-6 shadow-2xl transition-colors dark:bg-[#1f2c25]">
                <h3 class="text-lg font-black text-[#263c32] dark:text-[#f3f1e8]">Pelunasan Dipercepat</h3>
                <p class="mt-1 text-xs text-current/60">
                    Sistem akan mencatat 1 transaksi pembayaran pelunasan dan otomatis membatalkan seluruh sisa cicilan mendatang, lalu menandai pinjaman lunas.
                </p>

                <form wire:submit.prevent="executeAcceleratedSettlement" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-current/70">Nominal Pelunasan (Rp)</label>
                        <input type="text" wire:model="settlementAmount" placeholder="mis. Sisa pokok"
                               class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-base font-bold focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                        @error('settlementAmount') <span class="text-xs text-[#ad3f45]">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-current/70">Sumber Akun Pembayaran</label>
                        <select wire:model="settlementAccountId"
                                class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none">
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }} ({{ $acc->type->label() }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-current/70">Tanggal Pelunasan</label>
                        <input type="date" wire:model="settlementDate"
                               class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-current/70">Catatan (Opsional)</label>
                        <input type="text" wire:model="settlementNote" placeholder="mis. Pelunasan lebih awal diskon bunga"
                               class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-none" />
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showAcceleratedModal', false)"
                                class="rounded-xl px-4 py-2 text-xs font-bold text-current/70 hover:bg-current/10">
                            Batal
                        </button>
                        <button type="submit"
                                class="rounded-xl bg-amber-600 px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-amber-700">
                            Proses Pelunasan Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
