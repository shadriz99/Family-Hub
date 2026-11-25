<div class="mx-auto max-w-xl space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('finance.transactions.index') }}" class="text-xs font-bold text-current/60 hover:underline">
            &larr; Kembali ke Daftar Transaksi
        </a>

        <button wire:click="deleteTransaction"
                wire:confirm="Yakin ingin menghapus transaksi ini? Saldo akun akan dikembalikan."
                class="rounded-xl px-3 py-1.5 text-xs font-bold text-[#ad3f45] hover:bg-rose-500/10">
            Hapus Transaksi
        </button>
    </div>

    <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-6 shadow-sm transition-colors dark:bg-[#1f2c25]">
        <div class="flex items-center justify-between border-b border-current/10 pb-4">
            <div class="flex items-center gap-3">
                <div class="flex size-11 items-center justify-center rounded-2xl {{ $tx->type->value === 'income' ? 'bg-[#28543e]/10 dark:bg-[#bad6ae]/15 text-[#28543e]' : ($tx->type->value === 'transfer' ? 'bg-blue-500/10 text-blue-600' : 'bg-rose-500/10 text-[#ad3f45]') }}">
                    @if($tx->type->value === 'income')
                        <x-heroicon-o-arrow-down-left class="size-6" />
                    @elseif($tx->type->value === 'transfer')
                        <x-heroicon-o-arrows-right-left class="size-6" />
                    @else
                        <x-heroicon-o-arrow-up-right class="size-6" />
                    @endif
                </div>
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-current/50">{{ $tx->type->label() }}</span>
                    <h2 class="text-lg font-black text-[#263c32] dark:text-[#f3f1e8]">
                        {{ $tx->category?->name ?? ($tx->type->value === 'transfer' ? 'Transfer Antar Akun' : 'Transaksi') }}
                    </h2>
                </div>
            </div>

            <div class="text-right">
                <div class="text-2xl font-black {{ $tx->type->value === 'income' ? 'text-[#28543e] dark:text-[#bad6ae]' : ($tx->type->value === 'transfer' ? 'text-blue-600 dark:text-blue-400' : 'text-[#ad3f45] dark:text-[#ffafb1]') }}">
                    <x-money :amount="$tx->amount" />
                </div>
                <div class="text-xs text-current/50">{{ $tx->occurred_on->translatedFormat('d F Y') }}</div>
            </div>
        </div>

        <div class="mt-5 space-y-3 divide-y divide-current/5 text-xs sm:text-sm">
            <div class="flex justify-between py-2">
                <span class="text-current/60">Akun Sumber</span>
                <span class="font-bold text-[#263c32] dark:text-[#f3f1e8]">{{ $tx->account->name }}</span>
            </div>

            @if($tx->toAccount)
                <div class="flex justify-between py-2">
                    <span class="text-current/60">Akun Tujuan</span>
                    <span class="font-bold text-[#263c32] dark:text-[#f3f1e8]">{{ $tx->toAccount->name }}</span>
                </div>
            @endif

            @if($tx->payee)
                <div class="flex justify-between py-2">
                    <span class="text-current/60">Penerima / Pihak</span>
                    <span class="font-bold text-[#263c32] dark:text-[#f3f1e8]">{{ $tx->payee }}</span>
                </div>
            @endif

            @if($tx->note)
                <div class="flex justify-between py-2">
                    <span class="text-current/60">Catatan</span>
                    <span class="font-medium text-[#263c32] dark:text-[#f3f1e8]">{{ $tx->note }}</span>
                </div>
            @endif

            @if($tx->allocation)
                <div class="flex justify-between py-2">
                    <span class="text-current/60">Alokasi Anggaran</span>
                    <span class="font-bold text-[#28543e] dark:text-[#bad6ae]">{{ $tx->allocation->label() }}</span>
                </div>
            @endif

            @if($tx->allocation_detail)
                <div class="flex justify-between py-2">
                    <span class="text-current/60">Tujuan Penempatan Dana</span>
                    <span class="font-bold text-teal-600 dark:text-teal-400">{{ $tx->allocation_detail }}</span>
                </div>
            @endif

            @if($tx->plannedItem)
                <div class="flex justify-between py-2">
                    <span class="text-current/60">Terkait Rencana</span>
                    <a href="{{ route('finance.plan.index') }}#item-{{ $tx->plannedItem->id }}" class="font-bold text-blue-600 underline">
                        {{ $tx->plannedItem->name }}
                    </a>
                </div>
            @endif

            <div class="flex justify-between py-2">
                <span class="text-current/60">Dicatat Oleh</span>
                <span class="font-medium text-[#263c32] dark:text-[#f3f1e8]">{{ $tx->creator?->name ?? 'Sistem' }} ({{ $tx->created_at?->diffForHumans() }})</span>
            </div>
        </div>
    </div>
</div>
