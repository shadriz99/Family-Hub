<div id="item-{{ $item->id }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between rounded-2xl border border-current/10 bg-current/5 p-3.5 gap-3">
    <div class="flex items-center gap-3">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-current/10 text-current/80">
            @if($item->source_type->value === 'loan')
                <x-heroicon-o-document-currency-bangladeshi class="size-5" />
            @elseif($item->source_type->value === 'recurring_bill')
                <x-heroicon-o-arrow-path class="size-5" />
            @else
                <x-heroicon-o-calendar class="size-5" />
            @endif
        </div>
        <div>
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-[#263c32] dark:text-[#f3f1e8]">{{ $item->name }}</span>
                <span class="rounded px-1.5 py-0.5 text-[10px] font-bold {{ $badgeColor }}">{{ $badge }}</span>
            </div>
            <div class="text-xs text-current/60 flex items-center gap-2">
                <span>Jatuh tempo: {{ $item->due_date->translatedFormat('d M Y') }}</span>
                @if($item->category)
                    <span>&bull;</span>
                    <span>{{ $item->category->name }}</span>
                @endif
                @if($item->account)
                    <span>&bull;</span>
                    <span>{{ $item->account->name }}</span>
                @endif
            </div>
            @if($item->status_reason)
                <div class="text-[11px] italic text-current/50">Alasan: {{ $item->status_reason }}</div>
            @endif
        </div>
    </div>

    <div class="flex items-center justify-between sm:justify-end gap-3 self-end sm:self-auto w-full sm:w-auto">
        <div class="text-right">
            <div class="text-sm font-black text-rose-600 dark:text-rose-400">
                <x-money :amount="$item->remainingAmount()" />
            </div>
            @if($item->paid_amount > 0 && $item->paid_amount < $item->amount)
                <div class="text-[10px] text-current/50">
                    Terbayar: <x-money :amount="$item->paid_amount" /> dari <x-money :amount="$item->amount" />
                </div>
            @endif
        </div>

        <div class="flex items-center gap-1.5">
            @if(in_array($item->status->value, ['planned', 'partial']))
                <button wire:click="openPay('{{ $item->id }}')"
                        class="rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 active:scale-95">
                    Bayar
                </button>
                <button wire:click="openSkip('{{ $item->id }}')"
                        class="rounded-xl px-2.5 py-1.5 text-xs font-semibold text-current/60 hover:bg-current/10">
                    Lewati
                </button>
            @elseif($item->status->value === 'skipped')
                <button wire:click="unskipItem('{{ $item->id }}')"
                        class="rounded-xl px-2.5 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-500/10">
                    Batalkan Skip
                </button>
            @endif
        </div>
    </div>
</div>
