<div class="rounded-2xl border border-current/10 bg-[#fffefa] p-5 shadow-sm transition-colors dark:bg-[#1f2c25]">
    <div class="flex items-center justify-between border-b border-current/10 pb-4">
        <div class="flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                <x-heroicon-o-wallet class="size-6" />
            </div>
            <div>
                <h3 class="text-sm font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">Ringkasan Keuangan</h3>
                @if($currentPeriod)
                    <p class="text-xs text-current/60">Periode {{ $currentPeriod->start->translatedFormat('d M') }} - {{ $currentPeriod->end->translatedFormat('d M Y') }}</p>
                @endif
            </div>
        </div>
        <a href="{{ route('finance.transactions.create') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition-all hover:bg-emerald-700 active:scale-95">
            <x-heroicon-o-plus class="size-4" />
            <span>Catat</span>
        </a>
    </div>

    {{-- Saldo Cards --}}
    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-current/10 bg-current/5 p-3">
            <span class="text-xs font-medium text-current/60">Saldo Operasional</span>
            <div class="mt-1 text-base sm:text-lg font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">
                <x-money :amount="$operationalBalance" />
            </div>
        </div>

        <div class="rounded-xl border border-current/10 bg-current/5 p-3">
            <span class="text-xs font-medium text-current/60">Saldo Tabungan</span>
            <div class="mt-1 text-base sm:text-lg font-bold tracking-tight text-emerald-600 dark:text-emerald-400">
                <x-money :amount="$savingsBalance" />
            </div>
        </div>

        <div class="col-span-2 sm:col-span-1 rounded-xl border border-current/10 bg-current/5 p-3">
            <span class="text-xs font-medium text-current/60">Total Gabungan</span>
            <div class="mt-1 text-base sm:text-lg font-bold tracking-tight text-current/80">
                <x-money :amount="$totalCombined" />
            </div>
        </div>
    </div>

    {{-- Kewajiban Belum Dibayar --}}
    <div class="mt-4 flex items-center justify-between rounded-xl bg-amber-500/10 px-4 py-3 text-amber-900 dark:text-amber-200">
        <div class="flex items-center gap-2">
            <x-heroicon-o-clock class="size-5 text-amber-600 dark:text-amber-400" />
            <div class="text-xs sm:text-sm">
                <span class="font-bold">{{ $unpaidCount }} Kewajiban Belum Dibayar</span>
                <span class="text-xs text-current/70 block sm:inline sm:ml-1">periode ini</span>
            </div>
        </div>
        <div class="text-right">
            <span class="text-sm font-bold text-amber-700 dark:text-amber-300">
                <x-money :amount="$unpaidTotal" />
            </span>
            <a href="{{ route('finance.plan.index') }}" class="block text-xs font-semibold text-emerald-600 underline dark:text-emerald-400">
                Buka Rencana &rarr;
            </a>
        </div>
    </div>
</div>
