<div class="space-y-6">
    {{-- Top Header & Quick Actions --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-[#263c32] dark:text-[#f3f1e8]">Keuangan</h1>
            <p class="text-xs sm:text-sm text-current/60">
                Periode aktif: <span class="font-semibold">{{ $currentPeriod->start->translatedFormat('d M') }} - {{ $currentPeriod->end->translatedFormat('d M Y') }}</span>
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('finance.transactions.create') }}"
               class="inline-flex items-center gap-2 rounded-2xl bg-[#28543e] px-4 py-2.5 text-sm font-bold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-sm transition-all hover:opacity-90 active:scale-95">
                <x-heroicon-o-plus class="size-5" />
                <span>Catat Transaksi</span>
            </a>
        </div>
    </div>

    {{-- Main Balances Banner --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <div class="flex items-center justify-between text-xs font-semibold text-current/60">
                <span>Saldo Operasional</span>
                <span class="size-2 rounded-full bg-[#28543e] dark:bg-[#bad6ae]"></span>
            </div>
            <div class="mt-2 text-2xl font-black tracking-tight text-[#263c32] dark:text-[#f3f1e8]">
                <x-money :amount="$operationalBalance" />
            </div>
            <p class="mt-1 text-xs text-current/50">Kas, bank, dompet digital, kartu kredit</p>
        </div>

        <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <div class="flex items-center justify-between text-xs font-semibold text-current/60">
                <span>Saldo Tabungan</span>
                <span class="size-2 rounded-full bg-teal-500"></span>
            </div>
            <div class="mt-2 text-2xl font-black tracking-tight text-[#28543e] dark:text-[#bad6ae]">
                <x-money :amount="$savingsBalance" />
            </div>
            <p class="mt-1 text-xs text-current/50">Dana darurat, masa depan, investasi</p>
        </div>

        <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <div class="flex items-center justify-between text-xs font-semibold text-current/60">
                <span>Total Gabungan</span>
                <span class="size-2 rounded-full bg-current/30"></span>
            </div>
            <div class="mt-2 text-2xl font-black tracking-tight text-current/80">
                <x-money :amount="$totalCombined" />
            </div>
            <p class="mt-1 text-xs text-current/50">Operasional + Tabungan</p>
        </div>
    </div>

    {{-- Horizontal Scrollable Accounts --}}
    <div>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">Akun & Dompet</h2>
            <a href="{{ route('finance.accounts.index') }}" class="text-xs font-bold text-[#28543e] hover:underline dark:text-[#bad6ae]">
                Kelola Akun &rarr;
            </a>
        </div>

        <div class="no-scrollbar -mx-4 flex gap-3 overflow-x-auto px-4 sm:mx-0 sm:px-0">
            @forelse($accounts as $item)
                @php
                    $acc = $item['account'];
                    $bal = $item['balance'];
                @endphp
                <div class="min-w-[190px] max-w-[220px] shrink-0 rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-sm transition-colors dark:bg-[#1f2c25]">
                    <div class="flex items-center justify-between">
                        <span class="inline-flex size-3 rounded-full" style="background-color: {{ $acc->color }}"></span>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-current/50">{{ $acc->type->label() }}</span>
                    </div>
                    <div class="mt-2 font-bold text-sm truncate text-[#263c32] dark:text-[#f3f1e8]">{{ $acc->name }}</div>
                    <div class="mt-1 text-base font-black tracking-tight {{ $bal < 0 ? 'text-[#ad3f45] dark:text-[#ffafb1]' : 'text-current/90' }}">
                        <x-money :amount="$bal" />
                    </div>
                </div>
            @empty
                <div class="w-full rounded-2xl border border-dashed border-current/15 p-6 text-center text-xs text-current/60">
                    Belum ada akun terdaftar. <a href="{{ route('finance.accounts.index') }}" class="font-bold underline text-[#28543e]">Tambah Akun Baru</a>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Navigation Feature Badges --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <a href="{{ route('finance.plan.index') }}" class="flex items-center gap-3 rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-sm transition-all hover:border-[#28543e]/50 dark:hover:border-[#bad6ae]/50 hover:shadow-md dark:bg-[#1f2c25]">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
                <x-heroicon-o-calendar class="size-5" />
            </div>
            <div>
                <div class="text-sm font-bold text-[#263c32] dark:text-[#f3f1e8]">Rencana</div>
                <div class="text-xs text-current/60">Rencana Bulanan</div>
            </div>
        </a>

        <a href="{{ route('finance.budgets.index') }}" class="flex items-center gap-3 rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-sm transition-all hover:border-[#28543e]/50 dark:hover:border-[#bad6ae]/50 hover:shadow-md dark:bg-[#1f2c25]">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-[#87571f] dark:text-[#edc48b]">
                <x-heroicon-o-chart-pie class="size-5" />
            </div>
            <div>
                <div class="text-sm font-bold text-[#263c32] dark:text-[#f3f1e8]">Anggaran</div>
                <div class="text-xs text-current/60">Batas Kategori</div>
            </div>
        </a>

        <a href="{{ route('finance.loans.index') }}" class="flex items-center gap-3 rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-sm transition-all hover:border-[#28543e]/50 dark:hover:border-[#bad6ae]/50 hover:shadow-md dark:bg-[#1f2c25]">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400">
                <x-heroicon-o-document-currency-bangladeshi class="size-5" />
            </div>
            <div>
                <div class="text-sm font-bold text-[#263c32] dark:text-[#f3f1e8]">Pinjaman</div>
                <div class="text-xs text-current/60">Hutang & Piutang</div>
            </div>
        </a>

        <a href="{{ route('finance.reports.index') }}" class="flex items-center gap-3 rounded-2xl border border-current/10 bg-[#fffefa] p-4 shadow-sm transition-all hover:border-[#28543e]/50 dark:hover:border-[#bad6ae]/50 hover:shadow-md dark:bg-[#1f2c25]">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-rose-500/10 text-[#ad3f45] dark:text-[#ffafb1]">
                <x-heroicon-o-presentation-chart-line class="size-5" />
            </div>
            <div>
                <div class="text-sm font-bold text-[#263c32] dark:text-[#f3f1e8]">Laporan</div>
                <div class="text-xs text-current/60">Arus Kas & Tren</div>
            </div>
        </a>
    </div>

    {{-- Split Columns: Kewajiban 7 Hari & Transaksi Terbaru --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Upcoming Obligations --}}
        <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">Kewajiban 7 Hari Ke Depan</h2>
                    <p class="text-xs text-current/60">Tagihan & cicilan yang akan jatuh tempo</p>
                </div>
                <a href="{{ route('finance.plan.index') }}" class="text-xs font-bold text-[#28543e] hover:underline dark:text-[#bad6ae]">
                    Lihat Semua
                </a>
            </div>

            <div class="space-y-3">
                @forelse($upcomingObligations as $item)
                    <div class="flex items-center justify-between rounded-2xl border border-current/10 bg-current/5 p-3.5">
                        <div class="flex items-center gap-3">
                            <div class="flex size-9 items-center justify-center rounded-xl bg-rose-500/10 text-[#ad3f45] dark:text-[#ffafb1]">
                                <x-heroicon-o-clock class="size-5" />
                            </div>
                            <div>
                                <div class="text-sm font-bold text-[#263c32] dark:text-[#f3f1e8]">{{ $item->name }}</div>
                                <div class="text-xs text-current/60">
                                    Jatuh tempo: {{ $item->due_date->translatedFormat('d M Y') }}
                                    @if($item->isOverdue())
                                        <span class="ml-1 rounded px-1.5 py-0.5 text-[10px] font-bold bg-rose-500/20 text-[#ad3f45] dark:text-[#ffafb1]">Terlambat</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-sm font-black text-[#ad3f45] dark:text-[#ffafb1]">
                                <x-money :amount="$item->remainingAmount()" />
                            </div>
                            <a href="{{ route('finance.plan.index') }}#item-{{ $item->id }}" class="text-xs font-semibold text-[#28543e] underline dark:text-[#bad6ae]">
                                Bayar
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-current/15 p-6 text-center text-xs text-current/60">
                        Tidak ada kewajiban dalam 7 hari ke depan.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Latest Transactions --}}
        <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">Transaksi Terbaru</h2>
                    <p class="text-xs text-current/60">5 transaksi terakhir tercatat</p>
                </div>
                <a href="{{ route('finance.transactions.index') }}" class="text-xs font-bold text-[#28543e] hover:underline dark:text-[#bad6ae]">
                    Lihat Semua
                </a>
            </div>

            <div class="space-y-3">
                @forelse($latestTransactions as $tx)
                    <a href="{{ route('finance.transactions.show', $tx->id) }}" class="flex items-center justify-between rounded-2xl border border-current/10 bg-current/5 p-3.5 transition-colors hover:bg-current/10">
                        <div class="flex items-center gap-3">
                            <div class="flex size-9 items-center justify-center rounded-xl {{ $tx->type->value === 'income' ? 'bg-[#28543e]/10 dark:bg-[#bad6ae]/15 text-[#28543e] dark:text-[#bad6ae]' : ($tx->type->value === 'transfer' ? 'bg-blue-500/10 text-blue-600 dark:text-blue-400' : 'bg-rose-500/10 text-[#ad3f45] dark:text-[#ffafb1]') }}">
                                @if($tx->type->value === 'income')
                                    <x-heroicon-o-arrow-down-left class="size-5" />
                                @elseif($tx->type->value === 'transfer')
                                    <x-heroicon-o-arrows-right-left class="size-5" />
                                @else
                                    <x-heroicon-o-arrow-up-right class="size-5" />
                                @endif
                            </div>
                            <div>
                                <div class="text-sm font-bold text-[#263c32] dark:text-[#f3f1e8]">
                                    {{ $tx->category?->name ?? ($tx->type->value === 'transfer' ? 'Transfer Antar Akun' : 'Transaksi') }}
                                </div>
                                <div class="text-xs text-current/60 flex items-center gap-2">
                                    <span>{{ $tx->account->name }}</span>
                                    <span>&bull;</span>
                                    <span>{{ $tx->occurred_on->translatedFormat('d M') }}</span>
                                    @if($tx->creator)
                                        <span>&bull;</span>
                                        <span>{{ $tx->creator->name }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-sm font-black {{ $tx->type->value === 'income' ? 'text-[#28543e] dark:text-[#bad6ae]' : ($tx->type->value === 'transfer' ? 'text-blue-600 dark:text-blue-400' : 'text-[#ad3f45] dark:text-[#ffafb1]') }}">
                                {{ $tx->type->value === 'income' ? '+' : ($tx->type->value === 'expense' ? '-' : '') }}
                                <x-money :amount="$tx->amount" />
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="rounded-2xl border border-dashed border-current/15 p-6 text-center text-xs text-current/60">
                        Belum ada transaksi tercatat.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
