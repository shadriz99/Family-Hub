<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-[#263c32] dark:text-[#f3f1e8]">Laporan Keuangan</h1>
            <p class="text-xs sm:text-sm text-current/60">Analisis arus kas, distribusi pengeluaran, tren bulanan, dan posisi hutang</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-period-switcher
                :current="$period->start->translatedFormat('d M') . ' - ' . $period->end->translatedFormat('d M Y')"
                :prevUrl="route('finance.reports.index', ['period' => $prevPeriod->key])"
                :nextUrl="route('finance.reports.index', ['period' => $nextPeriod->key])"
            />

            <a href="{{ route('finance.export.transactions', ['period' => $period->key]) }}"
               class="inline-flex items-center gap-1.5 rounded-2xl border border-current/15 bg-current/5 px-3.5 py-2 text-xs sm:text-sm font-bold hover:bg-current/10">
                <x-heroicon-o-arrow-down-tray class="size-4" />
                <span>Ekspor CSV</span>
            </a>
        </div>
    </div>

    {{-- Period Cash Flow Summary Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <span class="text-xs font-semibold text-current/60">Total Pemasukan Riil</span>
            <div class="mt-2 text-2xl font-black tracking-tight text-emerald-600 dark:text-emerald-400">
                <x-money :amount="$totalIncome" />
            </div>
            <p class="mt-1 text-xs text-current/50">Seluruh uang masuk aktual periode ini</p>
        </div>

        <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <span class="text-xs font-semibold text-current/60">Total Pengeluaran Riil</span>
            <div class="mt-2 text-2xl font-black tracking-tight text-rose-600 dark:text-rose-400">
                <x-money :amount="$totalExpense" />
            </div>
            <p class="mt-1 text-xs text-current/50">Kewajiban + belanja variabel</p>
        </div>

        <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <span class="text-xs font-semibold text-current/60">Arus Kas Bersih (Net)</span>
            <div class="mt-2 text-2xl font-black tracking-tight {{ $netCashFlow < 0 ? 'text-rose-600' : 'text-emerald-600 dark:text-emerald-400' }}">
                <x-money :amount="$netCashFlow" />
            </div>
            <p class="mt-1 text-xs text-current/50">{{ $netCashFlow >= 0 ? 'Surplus tabungan' : 'Defisit periode' }}</p>
        </div>
    </div>

    {{-- Liabilities & Receivables Summary (US-FIN-09 AC4) --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-rose-600">Total Sisa Hutang Keluarga</span>
                <x-heroicon-o-document-currency-bangladeshi class="size-5 text-rose-600" />
            </div>
            <div class="mt-2 text-2xl font-black text-rose-600 dark:text-rose-400">
                <x-money :amount="$totalPayableRemaining" />
            </div>
            <p class="mt-1 text-xs text-current/50">Total kewajiban pinjaman yang masih harus dilunasi</p>
        </div>

        <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600">Total Sisa Piutang (Hak Tagih)</span>
                <x-heroicon-o-document-currency-bangladeshi class="size-5 text-emerald-600" />
            </div>
            <div class="mt-2 text-2xl font-black text-emerald-600 dark:text-emerald-400">
                <x-money :amount="$totalReceivableRemaining" />
            </div>
            <p class="mt-1 text-xs text-current/50">Total pinjaman yang dipinjamkan ke pihak lain</p>
        </div>
    </div>

    {{-- Expense Distribution by Category (US-FIN-09 AC2) --}}
    <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 sm:p-6 shadow-sm transition-colors dark:bg-[#1f2c25]">
        <div class="mb-4">
            <h2 class="text-base font-bold text-[#263c32] dark:text-[#f3f1e8]">Distribusi Pengeluaran per Kategori</h2>
            <p class="text-xs text-current/60">Peringkat pos pengeluaran terbesar periode {{ $period->start->translatedFormat('F Y') }}</p>
        </div>

        <div class="space-y-3">
            @forelse($categoryBreakdown as $row)
                <div>
                    <div class="flex items-center justify-between text-xs font-bold mb-1">
                        <span class="text-[#263c32] dark:text-[#f3f1e8]">{{ $row['category']->name }}</span>
                        <span>
                            <x-money :amount="$row['amount']" />
                            <span class="text-current/50 font-normal">({{ $row['percentage'] }}%)</span>
                        </span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-current/10">
                        <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $row['percentage'] }}%"></div>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-current/15 p-8 text-center text-xs text-current/60">
                    Belum ada pengeluaran tercatat pada periode ini.
                </div>
            @endforelse
        </div>
    </div>

    {{-- 12 Periods Cash Flow Trend (US-FIN-09 AC3) --}}
    <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 sm:p-6 shadow-sm transition-colors dark:bg-[#1f2c25]">
        <div class="mb-4">
            <h2 class="text-base font-bold text-[#263c32] dark:text-[#f3f1e8]">Tren Arus Kas (12 Periode Terakhir)</h2>
            <p class="text-xs text-current/60">Perbandingan pergerakan pemasukan, pengeluaran, dan surplus tabungan</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-current/10 text-current/60">
                        <th class="py-2 font-bold">Periode</th>
                        <th class="py-2 font-bold text-right">Pemasukan</th>
                        <th class="py-2 font-bold text-right">Pengeluaran</th>
                        <th class="py-2 font-bold text-right">Selisih Bersih</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-current/5">
                    @foreach($twelvePeriods as $tp)
                        <tr class="hover:bg-current/5">
                            <td class="py-2.5 font-bold">{{ $tp['label'] }}</td>
                            <td class="py-2.5 text-right text-emerald-600 dark:text-emerald-400 font-semibold">
                                <x-money :amount="$tp['income']" />
                            </td>
                            <td class="py-2.5 text-right text-rose-600 dark:text-rose-400 font-semibold">
                                <x-money :amount="$tp['expense']" />
                            </td>
                            <td class="py-2.5 text-right font-black {{ $tp['net'] < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                <x-money :amount="$tp['net']" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
