<div class="space-y-6">
    {{-- Header & Period Switcher --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-[#263c32] dark:text-[#f3f1e8]">Anggaran Kategori</h1>
            <p class="text-xs sm:text-sm text-current/60">Pengaturan anggaran berbasis persentase dari keseluruhan pemasukan (Total 100%)</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-period-switcher
                :current="$period->start->translatedFormat('d M') . ' - ' . $period->end->translatedFormat('d M Y')"
                :prevUrl="route('finance.budgets.index', ['period' => $prevPeriod->key])"
                :nextUrl="route('finance.budgets.index', ['period' => $nextPeriod->key])"
            />

            <button wire:click="openConfigure"
                    class="inline-flex items-center gap-1.5 rounded-2xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 active:scale-95">
                <x-heroicon-o-adjustments-horizontal class="size-4" />
                <span>Atur Persentase (100%)</span>
            </button>

            <button wire:click="copyPrevious"
                    class="inline-flex items-center gap-1.5 rounded-2xl border border-current/15 bg-current/5 px-3 py-2 text-xs font-bold hover:bg-current/10">
                <x-heroicon-o-document-duplicate class="size-4" />
                <span>Salin Lalu</span>
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="rounded-2xl bg-emerald-500/10 p-4 text-xs sm:text-sm font-semibold text-emerald-700 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    {{-- Overall Income & Budget Distribution Summary --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <span class="text-xs font-semibold text-current/60">Keseluruhan Pemasukan</span>
            <div class="mt-2 text-2xl font-black tracking-tight text-emerald-600 dark:text-emerald-400">
                <x-money :amount="$totalIncome" />
            </div>
            <p class="mt-1 text-[11px] text-current/50">Basis kalkulasi persentase anggaran</p>
        </div>

        <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <span class="text-xs font-semibold text-current/60">Total Plafon Anggaran</span>
            <div class="mt-2 text-2xl font-black tracking-tight text-[#263c32] dark:text-[#f3f1e8]">
                <x-money :amount="$totalBudget" />
            </div>
            <p class="mt-1 text-[11px] text-current/50">Akumulasi 100% dari alokasi pos</p>
        </div>

        <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <span class="text-xs font-semibold text-current/60">Pengeluaran Aktual</span>
            <div class="mt-2 text-2xl font-black tracking-tight text-rose-600 dark:text-rose-400">
                <x-money :amount="$totalSpent" />
            </div>
            <p class="mt-1 text-[11px] text-current/50">Tidak termasuk kewajiban planned (BR-FIN-030)</p>
        </div>

        <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-sm transition-colors dark:bg-[#1f2c25]">
            <span class="text-xs font-semibold text-current/60">Sisa Anggaran Tersedia</span>
            <div class="mt-2 text-2xl font-black tracking-tight {{ $totalRemaining < 0 ? 'text-rose-600' : 'text-emerald-600 dark:text-emerald-400' }}">
                <x-money :amount="$totalRemaining" />
            </div>
            <p class="mt-1 text-[11px] text-current/50">{{ $totalBudget > 0 ? round(($totalSpent / $totalBudget) * 100, 1) : 0 }}% terpakai</p>
        </div>
    </div>

    {{-- Visual 100% Distribution Bar --}}
    <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-sm dark:bg-[#1f2c25]">
        <div class="flex items-center justify-between text-xs font-bold text-current/70 mb-3">
            <span>Komposisi Alokasi Anggaran (100% Pemasukan)</span>
            <span class="text-emerald-600 dark:text-emerald-400">5 Kategori Utama</span>
        </div>
        <div class="flex h-3.5 w-full overflow-hidden rounded-full bg-current/10">
            @foreach($budgetSummaries as $row)
                @php
                    $pct = $row['budget_percentage'] ?? 0;
                    $color = $row['category']->color ?? '#10b981';
                @endphp
                @if($pct > 0)
                    <div style="width: {{ $pct }}%; background-color: {{ $color }};"
                         title="{{ $row['category']->name }}: {{ $pct }}%"
                         class="h-full transition-all"></div>
                @endif
            @endforeach
        </div>
        <div class="mt-3 flex flex-wrap gap-4 text-xs">
            @foreach($budgetSummaries as $row)
                <div class="flex items-center gap-1.5">
                    <span class="size-2.5 rounded-full" style="background-color: {{ $row['category']->color ?? '#10b981' }};"></span>
                    <span class="font-semibold text-current/70">{{ $row['category']->name }}</span>
                    <span class="font-bold text-[#263c32] dark:text-[#f3f1e8]">{{ $row['budget_percentage'] ?? 0 }}%</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Category Cards --}}
    <div class="space-y-4">
        @forelse($budgetSummaries as $row)
            @php
                $cat = $row['category'];
                $budget = $row['budget'];
                $budgetPercentage = $row['budget_percentage'] ?? 0;
                $budgetAmount = $row['budget_amount'];
                $spentAmount = $row['spent_amount'];
                $remaining = $row['remaining_amount'];
                $usedPct = $row['used_percentage'] ?? 0;
            @endphp
            <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 sm:p-6 shadow-sm transition-colors dark:bg-[#1f2c25]">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl"
                             style="background-color: {{ ($cat->color ?? '#10b981') . '20' }}; color: {{ $cat->color ?? '#10b981' }};">
                            @if($cat->default_allocation?->value === 'living')
                                <x-heroicon-o-home class="size-6" />
                            @elseif($cat->default_allocation?->value === 'tertiary')
                                <x-heroicon-o-sparkles class="size-6" />
                            @elseif($cat->default_allocation?->value === 'savings')
                                <x-heroicon-o-banknotes class="size-6" />
                            @elseif($cat->default_allocation?->value === 'social')
                                <x-heroicon-o-gift class="size-6" />
                            @elseif($cat->default_allocation?->value === 'debt')
                                <x-heroicon-o-credit-card class="size-6" />
                            @else
                                <x-heroicon-o-folder class="size-6" />
                            @endif
                        </div>

                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base sm:text-lg font-bold text-[#263c32] dark:text-[#f3f1e8]">{{ $cat->name }}</h3>
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-bold bg-current/5 border border-current/10">
                                    {{ $budgetPercentage }}% Alokasi
                                </span>
                                @if($usedPct >= 100)
                                    <span class="rounded-full bg-rose-500/20 px-2 py-0.5 text-[10px] font-bold text-rose-600 dark:text-rose-400">100% Penuh</span>
                                @elseif($usedPct >= 80)
                                    <span class="rounded-full bg-amber-500/20 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:text-amber-300">Waspada 80%</span>
                                @endif
                            </div>

                            <div class="mt-1 text-xs text-current/60 flex flex-wrap gap-2">
                                <span>Plafon: <strong class="text-current/90"><x-money :amount="$budgetAmount" /></strong></span>
                                <span>&bull;</span>
                                <span>Terpakai: <strong class="text-rose-600 dark:text-rose-400"><x-money :amount="$spentAmount" /></strong></span>
                                <span>&bull;</span>
                                <span>Sisa: <strong class="{{ $remaining < 0 ? 'text-rose-600' : 'text-emerald-600 dark:text-emerald-400' }}"><x-money :amount="$remaining" /></strong></span>
                            </div>
                        </div>
                    </div>

                    <div class="text-right sm:min-w-[120px]">
                        <div class="text-xs font-semibold text-current/60">Tingkat Konsumsi</div>
                        <div class="text-xl font-black {{ $usedPct >= 100 ? 'text-rose-600' : ($usedPct >= 80 ? 'text-amber-600' : 'text-emerald-600 dark:text-emerald-400') }}">
                            {{ $usedPct }}%
                        </div>
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div class="mt-4">
                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-current/10">
                        <div class="h-full rounded-full transition-all {{ $usedPct >= 100 ? 'bg-rose-500' : ($usedPct >= 80 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                             style="width: {{ min(100, $usedPct) }}%"></div>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-8 text-center dark:bg-[#1f2c25]">
                <p class="text-sm font-semibold text-current/60">Belum ada kategori utama. Silakan inisialisasi kategori terlebih dahulu.</p>
            </div>
        @endforelse
    </div>

    {{-- Configure Percentage Modal --}}
    @if($showConfigureModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-lg rounded-3xl border border-current/10 bg-[#fffefa] p-6 shadow-2xl dark:bg-[#1f2c25]">
                <div class="flex items-center justify-between border-b border-current/10 pb-4">
                    <div>
                        <h2 class="text-lg font-black text-[#263c32] dark:text-[#f3f1e8]">Atur Persentase Anggaran</h2>
                        <p class="text-xs text-current/60">Alokasikan 100% dari total pemasukan ke 5 kategori utama</p>
                    </div>
                    <button wire:click="closeConfigure" class="rounded-xl p-1 text-current/50 hover:bg-current/10">
                        <x-heroicon-o-x-mark class="size-5" />
                    </button>
                </div>

                <div class="mt-4 space-y-4 max-h-[70vh] overflow-y-auto pr-1">
                    {{-- Income Base Setting --}}
                    <div>
                        <label class="block text-xs font-bold text-current/70">Estimasi / Total Pemasukan Periode Ini (Rp)</label>
                        <input type="text" wire:model="incomeBaseInput" placeholder="mis. 10jt atau 10000000"
                               class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm font-bold focus:border-emerald-500 focus:outline-none" />
                        @error('incomeBaseInput') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        <p class="mt-0.5 text-[11px] text-current/50">Jika sudah ada pemasukan aktual tercatat, sistem akan otomatis menggunakannya.</p>
                    </div>

                    {{-- Percentage per Category --}}
                    <div class="space-y-3 border-t border-current/10 pt-3">
                        <div class="text-xs font-bold text-current/70">Alokasi per Kategori Utama (%):</div>

                        @foreach($percentages as $catId => $pct)
                            @php
                                $cat = \App\Modules\Finance\Models\Category::find($catId);
                            @endphp
                            @if($cat)
                                <div class="flex items-center justify-between gap-3 rounded-2xl border border-current/10 bg-current/5 p-3">
                                    <div class="flex items-center gap-2.5">
                                        <span class="size-3 rounded-full" style="background-color: {{ $cat->color ?? '#10b981' }};"></span>
                                        <div>
                                            <div class="text-xs font-bold text-[#263c32] dark:text-[#f3f1e8]">{{ $cat->name }}</div>
                                            <div class="text-[10px] text-current/50 capitalize">{{ $cat->default_allocation?->label() ?? 'Pos Keuangan' }}</div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-1.5">
                                        <input type="number" min="0" max="100" wire:model.live="percentages.{{ $catId }}"
                                               class="w-16 rounded-xl border border-current/20 bg-transparent px-2.5 py-1.5 text-center text-sm font-black focus:border-emerald-500 focus:outline-none" />
                                        <span class="text-xs font-bold text-current/60">%</span>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    {{-- Total Indicator --}}
                    @php
                        $tot = $this->totalPercentage;
                    @endphp
                    <div class="flex items-center justify-between rounded-2xl p-3.5 border {{ $tot === 100 ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-800 dark:text-emerald-200' : 'border-rose-500/30 bg-rose-500/10 text-rose-800 dark:text-rose-200' }}">
                        <span class="text-xs font-bold">Total Alokasi Keseluruhan:</span>
                        <span class="text-base font-black">{{ $tot }}% / 100%</span>
                    </div>

                    @error('percentages')
                        <div class="rounded-xl bg-rose-500/10 p-2.5 text-xs font-bold text-rose-600 dark:text-rose-400">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mt-6 flex items-center justify-end gap-2 border-t border-current/10 pt-4">
                    <button type="button" wire:click="closeConfigure" class="rounded-xl px-4 py-2 text-xs font-bold text-current/60 hover:bg-current/10">
                        Batal
                    </button>
                    <button type="button" wire:click="savePercentages"
                            class="rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 active:scale-95 disabled:opacity-50"
                            {{ $tot !== 100 ? 'disabled' : '' }}>
                        Simpan Anggaran (100%)
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
