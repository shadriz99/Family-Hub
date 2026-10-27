<div class="space-y-6">
    {{-- Undo Toast --}}
    @if($undoTransactionId)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 7000)"
             class="flex items-center justify-between rounded-2xl bg-[#263c32] p-4 text-xs sm:text-sm font-semibold text-white shadow-xl dark:bg-[#fffefa] dark:text-[#263c32]">
            <div class="flex items-center gap-2">
                <x-heroicon-o-check-circle class="size-5 text-emerald-400 dark:text-emerald-600" />
                <span>{{ $undoMessage ?? 'Transaksi berhasil disimpan.' }}</span>
            </div>
            <button wire:click="undoLastTransaction" class="rounded-xl bg-white/20 px-3 py-1 font-bold underline hover:bg-white/30 dark:bg-black/10">
                Batalkan
            </button>
        </div>
    @endif

    {{-- Header & Period Switcher --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-[#263c32] dark:text-[#f3f1e8]">Transaksi</h1>
            <p class="text-xs sm:text-sm text-current/60">Semua catatan arus kas keluar, masuk, dan transfer</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-period-switcher
                :current="$period->start->translatedFormat('d M') . ' - ' . $period->end->translatedFormat('d M Y')"
                :prevUrl="route('finance.transactions.index', array_merge(request()->query(), ['period' => $prevPeriod->key]))"
                :nextUrl="route('finance.transactions.index', array_merge(request()->query(), ['period' => $nextPeriod->key]))"
            />

            <a href="{{ route('finance.export.transactions', request()->query()) }}"
               class="inline-flex items-center gap-1.5 rounded-2xl border border-current/15 bg-current/5 px-3 py-2 text-xs font-bold hover:bg-current/10"
               title="Ekspor CSV">
                <x-heroicon-o-arrow-down-tray class="size-4" />
                <span class="hidden sm:inline">Ekspor CSV</span>
            </a>

            <a href="{{ route('finance.transactions.create') }}"
               class="inline-flex items-center gap-1.5 rounded-2xl bg-emerald-600 px-4 py-2 text-xs sm:text-sm font-bold text-white shadow-sm hover:bg-emerald-700 active:scale-95">
                <x-heroicon-o-plus class="size-4" />
                <span>Catat</span>
            </a>
        </div>
    </div>

    {{-- Filter Chips & Search Bar --}}
    <div class="space-y-3 rounded-3xl border border-current/10 bg-[#fffefa] p-4 shadow-sm transition-colors dark:bg-[#1f2c25]">
        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="$set('type', 'all')"
                    class="rounded-xl px-3 py-1.5 text-xs font-bold transition-colors {{ $type === 'all' ? 'bg-emerald-600 text-white' : 'bg-current/5 text-current/70 hover:bg-current/10' }}">
                Semua
            </button>
            <button wire:click="$set('type', 'expense')"
                    class="rounded-xl px-3 py-1.5 text-xs font-bold transition-colors {{ $type === 'expense' ? 'bg-rose-600 text-white' : 'bg-current/5 text-current/70 hover:bg-current/10' }}">
                Pengeluaran
            </button>
            <button wire:click="$set('type', 'income')"
                    class="rounded-xl px-3 py-1.5 text-xs font-bold transition-colors {{ $type === 'income' ? 'bg-emerald-600 text-white' : 'bg-current/5 text-current/70 hover:bg-current/10' }}">
                Pemasukan
            </button>
            <button wire:click="$set('type', 'transfer')"
                    class="rounded-xl px-3 py-1.5 text-xs font-bold transition-colors {{ $type === 'transfer' ? 'bg-blue-600 text-white' : 'bg-current/5 text-current/70 hover:bg-current/10' }}">
                Transfer
            </button>
        </div>

        <div class="grid grid-cols-1 gap-2 sm:grid-cols-4">
            <div class="sm:col-span-1">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari catatan / pihak..."
                       class="w-full rounded-xl border border-current/20 bg-transparent px-3 py-1.5 text-xs focus:border-emerald-500 focus:outline-none" />
            </div>

            <div>
                <select wire:model.live="account_id"
                        class="w-full rounded-xl border border-current/20 bg-transparent px-3 py-1.5 text-xs focus:border-emerald-500 focus:outline-none">
                    <option value="">Semua Akun</option>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select wire:model.live="category_id"
                        class="w-full rounded-xl border border-current/20 bg-transparent px-3 py-1.5 text-xs focus:border-emerald-500 focus:outline-none">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->parent ? "{$cat->parent->name} > " : '' }}{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select wire:model.live="created_by"
                        class="w-full rounded-xl border border-current/20 bg-transparent px-3 py-1.5 text-xs focus:border-emerald-500 focus:outline-none">
                    <option value="">Semua Pencatat</option>
                    @foreach($members as $m)
                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Grouped Daily Transactions List (US-FIN-08 AC1) --}}
    <div class="space-y-4">
        @forelse($dailyGroups as $group)
            <div class="rounded-3xl border border-current/10 bg-[#fffefa] overflow-hidden shadow-sm transition-colors dark:bg-[#1f2c25]">
                {{-- Date Header with Subtotals --}}
                <div class="flex items-center justify-between border-b border-current/10 bg-current/5 px-4 py-2.5 text-xs font-bold text-current/80">
                    <div>
                        <span>{{ $group['date']->translatedFormat('l, d F Y') }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        @if($group['income_total'] > 0)
                            <span class="text-emerald-600 dark:text-emerald-400">
                                +<x-money :amount="$group['income_total']" />
                            </span>
                        @endif
                        @if($group['expense_total'] > 0)
                            <span class="text-rose-600 dark:text-rose-400">
                                -<x-money :amount="$group['expense_total']" />
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Transactions in Date --}}
                <div class="divide-y divide-current/5">
                    @foreach($group['transactions'] as $tx)
                        <a href="{{ route('finance.transactions.show', $tx->id) }}"
                           class="flex items-center justify-between p-4 transition-colors hover:bg-current/5">
                            <div class="flex items-center gap-3">
                                {{-- Avatar of Creator (US-FIN-08 AC3) --}}
                                <div class="size-8 shrink-0 rounded-full bg-emerald-600/10 text-emerald-700 flex items-center justify-center font-bold text-xs uppercase dark:bg-emerald-400/20 dark:text-emerald-300"
                                     title="Dicatat oleh {{ $tx->creator?->name ?? 'Sistem' }}">
                                    {{ substr($tx->creator?->name ?? 'S', 0, 1) }}
                                </div>

                                <div>
                                    <div class="text-sm font-bold text-[#263c32] dark:text-[#f3f1e8]">
                                        {{ $tx->category?->name ?? ($tx->type->value === 'transfer' ? "Transfer ke {$tx->toAccount?->name}" : 'Transaksi') }}
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 text-xs text-current/60">
                                        <span>{{ $tx->account->name }}</span>
                                        @if($tx->payee)
                                            <span>&bull;</span>
                                            <span>{{ $tx->payee }}</span>
                                        @endif
                                        @if($tx->note)
                                            <span>&bull;</span>
                                            <span class="italic text-current/50 truncate max-w-[200px]">{{ $tx->note }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="text-right">
                                <div class="text-sm font-black {{ $tx->type->value === 'income' ? 'text-emerald-600 dark:text-emerald-400' : ($tx->type->value === 'transfer' ? 'text-blue-600 dark:text-blue-400' : 'text-rose-600 dark:text-rose-400') }}">
                                    {{ $tx->type->value === 'income' ? '+' : ($tx->type->value === 'expense' ? '-' : '') }}
                                    <x-money :amount="$tx->amount" />
                                </div>
                                @if($tx->allocation)
                                    <span class="text-[10px] font-semibold text-current/40">{{ $tx->allocation->label() }}</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-3xl border border-dashed border-current/15 p-12 text-center text-xs text-current/60">
                Tidak ada transaksi yang cocok dengan filter pada periode ini.
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div>
        {{ $paginated->links() }}
    </div>
</div>
