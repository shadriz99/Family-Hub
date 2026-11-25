<x-card>
    <div class="flex items-center justify-between border-b border-current/10 pb-4">
        <div class="flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-xl bg-[#28543e]/10 text-[#28543e] dark:bg-[#bad6ae]/15 dark:text-[#bad6ae]">
                <x-heroicon-o-wallet class="size-6" />
            </div>
            <div>
                <h3 class="text-sm font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">{{ __('Ringkasan Keuangan') }}</h3>
                @if($currentPeriod)
                    <p class="text-xs text-[#637067] dark:text-[#b2beb2]">Periode {{ $currentPeriod->start->translatedFormat('d M') }} - {{ $currentPeriod->end->translatedFormat('d M Y') }}</p>
                @endif
            </div>
        </div>
        <a href="{{ route('finance.transactions.create') }}" class="inline-flex min-h-9 items-center gap-1.5 rounded-2xl bg-[#28543e] px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs transition-opacity hover:opacity-90 active:scale-95 dark:bg-[#bad6ae] dark:text-[#151f1b]">
            <x-heroicon-o-plus class="size-4" />
            <span>{{ __('Catat') }}</span>
        </a>
    </div>

    {{-- Saldo Cards --}}
    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-current/10 bg-current/5 p-3">
            <span class="text-xs font-medium text-[#637067] dark:text-[#b2beb2]">{{ __('Saldo Operasional') }}</span>
            <div class="mt-1 text-base sm:text-lg font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">
                <x-money :amount="$operationalBalance" />
            </div>
        </div>

        <div class="rounded-2xl border border-current/10 bg-current/5 p-3">
            <span class="text-xs font-medium text-[#637067] dark:text-[#b2beb2]">{{ __('Saldo Tabungan') }}</span>
            <div class="mt-1 text-base sm:text-lg font-bold tracking-tight text-[#28543e] dark:text-[#bad6ae]">
                <x-money :amount="$savingsBalance" />
            </div>
        </div>

        <div class="col-span-2 sm:col-span-1 rounded-2xl border border-current/10 bg-current/5 p-3">
            <span class="text-xs font-medium text-[#637067] dark:text-[#b2beb2]">{{ __('Total Gabungan') }}</span>
            <div class="mt-1 text-base sm:text-lg font-bold tracking-tight text-current/80">
                <x-money :amount="$totalCombined" />
            </div>
        </div>
    </div>

    {{-- Kewajiban Belum Dibayar --}}
    <div class="mt-4 flex items-center justify-between rounded-2xl border border-[#87571f]/20 bg-[#87571f]/10 px-4 py-3 text-[#87571f] dark:border-[#edc48b]/25 dark:bg-[#edc48b]/10 dark:text-[#edc48b]">
        <div class="flex items-center gap-2">
            <x-heroicon-o-clock class="size-5 shrink-0" />
            <div class="text-xs sm:text-sm">
                <span class="font-bold">{{ $unpaidCount }} {{ __('Kewajiban Belum Dibayar') }}</span>
                <span class="text-xs opacity-80 block sm:inline sm:ml-1">{{ __('periode ini') }}</span>
            </div>
        </div>
        <div class="text-right">
            <span class="text-sm font-bold">
                <x-money :amount="$unpaidTotal" />
            </span>
            <a href="{{ route('finance.plan.index') }}" class="block text-xs font-semibold text-[#28543e] underline dark:text-[#bad6ae]">
                {{ __('Buka Rencana') }} &rarr;
            </a>
        </div>
    </div>
</x-card>
