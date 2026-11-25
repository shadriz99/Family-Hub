<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-[#263c32] dark:text-[#f3f1e8]">Pinjaman & Cicilan</h1>
            <p class="text-xs sm:text-sm text-current/60">Kelola kewajiban hutang, piutang keluarga, dan pelunasan angsuran</p>
        </div>

        <a href="{{ route('finance.loans.create') }}"
           class="inline-flex items-center gap-1.5 rounded-2xl bg-[#28543e] px-4 py-2 text-xs sm:text-sm font-bold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-sm hover:opacity-90 active:scale-95 self-start sm:self-auto">
            <x-heroicon-o-plus class="size-4" />
            <span>Tambah Pinjaman</span>
        </a>
    </div>

    @if (session()->has('message'))
        <div class="rounded-2xl bg-[#28543e] dark:bg-[#bad6ae]/10 p-4 text-xs sm:text-sm font-semibold text-[#28543e] dark:text-[#bad6ae]">
            {{ session('message') }}
        </div>
    @endif

    {{-- Tabs: Hutang / Piutang / Lunas --}}
    <div class="flex gap-2 border-b border-current/10 pb-2">
        <button wire:click="selectTab('payable')"
                class="rounded-xl px-4 py-2 text-xs sm:text-sm font-bold transition-colors {{ $tab === 'payable' ? 'bg-[#ad3f45] dark:bg-[#ffafb1] dark:text-[#151f1b] text-white' : 'text-current/60 hover:bg-current/10' }}">
            Hutang (Kita Bayar)
        </button>
        <button wire:click="selectTab('receivable')"
                class="rounded-xl px-4 py-2 text-xs sm:text-sm font-bold transition-colors {{ $tab === 'receivable' ? 'bg-[#28543e] text-white dark:bg-[#bad6ae] dark:text-[#151f1b]' : 'text-current/60 hover:bg-current/10' }}">
            Piutang (Kita Terima)
        </button>
        <button wire:click="selectTab('paid_off')"
                class="rounded-xl px-4 py-2 text-xs sm:text-sm font-bold transition-colors {{ $tab === 'paid_off' ? 'bg-[#386578] dark:bg-[#a7cad6] dark:text-[#151f1b] text-white' : 'text-current/60 hover:bg-current/10' }}">
            Lunas
        </button>
    </div>

    {{-- Loans Grid --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($loans as $item)
            @php
                $loan = $item['loan'];
                $paidCount = $item['paidCount'];
                $totalCount = $item['totalCount'];
                $paidAmount = $item['paidAmount'];
                $remainingAmount = $item['remainingAmount'];
                $next = $item['nextInstallment'];
                $diff = $item['totalDiff'];
                $progressPct = $totalCount > 0 ? round(($paidCount / $totalCount) * 100) : 0;
            @endphp
            <div class="flex flex-col justify-between rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-sm transition-colors dark:bg-[#1f2c25]">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $loan->direction->value === 'payable' ? 'bg-rose-500/10 text-[#ad3f45]' : 'bg-[#28543e]/10 dark:bg-[#bad6ae]/15 text-[#28543e]' }}">
                            {{ $loan->direction->label() }}
                        </span>
                        <span class="text-xs font-semibold text-current/60">{{ $loan->counterparty }}</span>
                    </div>

                    <h3 class="mt-2 text-base font-bold text-[#263c32] dark:text-[#f3f1e8]">{{ $loan->name }}</h3>

                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                        <div class="rounded-xl bg-current/5 p-2.5">
                            <span class="text-current/50 block text-[10px]">Pokok Pinjaman</span>
                            <span class="font-bold text-[#263c32] dark:text-[#f3f1e8]"><x-money :amount="$loan->principal" /></span>
                        </div>
                        <div class="rounded-xl bg-current/5 p-2.5">
                            <span class="text-current/50 block text-[10px]">Sisa Pembayaran</span>
                            <span class="font-black {{ $loan->direction->value === 'payable' ? 'text-[#ad3f45]' : 'text-[#28543e]' }}">
                                <x-money :amount="$remainingAmount" />
                            </span>
                        </div>
                    </div>

                    {{-- Selisih Total (BR-FIN-042) --}}
                    @if($diff > 0)
                        <div class="mt-2 text-[11px] text-current/60">
                            Selisih total: <span class="font-bold text-[#87571f] dark:text-[#edc48b]"><x-money :amount="$diff" /></span>
                        </div>
                    @endif

                    {{-- Progress Bar --}}
                    <div class="mt-4">
                        <div class="flex items-center justify-between text-xs font-semibold text-current/60 mb-1">
                            <span>Progres: {{ $paidCount }}/{{ $totalCount }} cicilan</span>
                            <span>{{ $progressPct }}%</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-current/10">
                            <div class="h-full rounded-full bg-[#28543e] dark:bg-[#bad6ae] transition-all" style="width: {{ $progressPct }}%"></div>
                        </div>
                    </div>

                    @if($next)
                        <div class="mt-3 rounded-xl border border-current/10 bg-current/5 p-2.5 text-xs">
                            <span class="text-current/50 block text-[10px]">Cicilan Berikutnya:</span>
                            <div class="flex items-center justify-between font-bold mt-0.5">
                                <span>{{ $next->due_date->translatedFormat('d M Y') }}</span>
                                <span class="text-[#ad3f45]"><x-money :amount="$next->remainingAmount()" /></span>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-5 flex items-center justify-end gap-2 border-t border-current/10 pt-3">
                    <a href="{{ route('finance.loans.show', $loan->id) }}"
                       class="inline-flex items-center gap-1 rounded-xl bg-[#28543e] px-3 py-1.5 text-xs font-bold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-sm hover:opacity-90">
                        <span>Lihat Jadwal & Detail</span>
                        <x-heroicon-o-arrow-right class="size-3.5" />
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-3xl border border-dashed border-current/15 p-12 text-center text-xs text-current/60">
                Tidak ada data pinjaman pada tab ini.
            </div>
        @endforelse
    </div>
</div>
