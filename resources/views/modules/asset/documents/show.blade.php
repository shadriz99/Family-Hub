<div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('assets.documents') }}" class="text-xs text-stone-500 hover:text-stone-900 dark:hover:text-white">
                    ← Kembali ke Dokumen
                </a>
                <span class="text-stone-400">/</span>
                <span class="text-xs text-stone-600 dark:text-stone-400">{{ $doc->type->label() }}</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-stone-900 dark:text-white">
                {{ $doc->title }}
            </h1>
            <div class="mt-1 flex items-center gap-2 text-xs">
                @if($doc->asset)
                    <a href="{{ route('assets.show', $doc->asset->id) }}" class="text-emerald-600 hover:underline">
                        📦 {{ $doc->asset->name }}
                    </a>
                    <span>•</span>
                @endif
                <span class="rounded-md px-2 py-0.5 font-medium {{ $doc->status->value === 'superseded' ? 'bg-stone-100 text-stone-500' : ($doc->isExpired() ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700') }}">
                    {{ $doc->status->label() }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($doc->status->value === 'active')
                <button wire:click="openRenewModal" class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-emerald-500">
                    🔄 Perpanjang Dokumen
                </button>
            @endif
            <a href="{{ route('assets.documents.edit', $doc->id) }}" class="inline-flex items-center rounded-xl border border-stone-200 bg-white px-3.5 py-2 text-xs font-semibold text-stone-700 shadow-xs hover:bg-stone-50 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-300 dark:hover:bg-stone-800">
                ✏️ Edit
            </a>
        </div>
    </div>

    {{-- Detail Card --}}
    <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-xs dark:border-stone-800 dark:bg-stone-900">
        <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2 text-xs">
            <div>
                <dt class="text-stone-500 dark:text-stone-400">Nomor Dokumen / Polis</dt>
                <dd class="font-mono text-sm font-semibold text-stone-900 dark:text-white mt-0.5">{{ $doc->number ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-stone-500 dark:text-stone-400">Instansi / Penerbit</dt>
                <dd class="text-sm font-medium text-stone-900 dark:text-white mt-0.5">{{ $doc->issuer ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-stone-500 dark:text-stone-400">Tanggal Terbit</dt>
                <dd class="text-stone-900 dark:text-white mt-0.5">{{ $doc->issued_on ? $doc->issued_on->translatedFormat('d F Y') : '-' }}</dd>
            </div>
            <div>
                <dt class="text-stone-500 dark:text-stone-400">Berlaku Sampai (Kedaluwarsa)</dt>
                <dd class="text-sm font-bold text-stone-900 dark:text-white mt-0.5">
                    @if($doc->expires_on)
                        {{ $doc->expires_on->translatedFormat('d F Y') }}
                        @if($doc->isExpired())
                            <span class="text-xs text-rose-500 font-normal">(Sudah kedaluwarsa)</span>
                        @endif
                    @else
                        -
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-stone-500 dark:text-stone-400">Estimasi Biaya Perpanjangan</dt>
                <dd class="text-stone-900 dark:text-white mt-0.5">{{ $doc->renewal_cost_estimate ? 'Rp ' . number_format($doc->renewal_cost_estimate, 0, ',', '.') : '-' }}</dd>
            </div>
            <div>
                <dt class="text-stone-500 dark:text-stone-400">Rencana Pengeluaran (Finance)</dt>
                <dd class="text-stone-900 dark:text-white mt-0.5">{{ $doc->add_to_finance_plan ? 'Aktif terhubung ke PlannedItem' : 'Tidak aktif' }}</dd>
            </div>
        </dl>

        @if($doc->notes)
            <div class="mt-5 border-t border-stone-100 pt-4 dark:border-stone-800 text-xs">
                <span class="text-stone-500 dark:text-stone-400 block mb-1">Catatan</span>
                <p class="text-stone-700 dark:text-stone-300 whitespace-pre-line">{{ $doc->notes }}</p>
            </div>
        @endif
    </div>

    {{-- Renew Modal (US-AST-03 AC3) --}}
    @if($showRenewModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-stone-900">
                <h3 class="text-base font-bold text-stone-900 dark:text-white">Perpanjang Dokumen: {{ $doc->title }}</h3>
                <p class="mt-1 text-xs text-stone-500">Dokumen lama akan ditandai superseded dan dokumen baru masa berlaku baru dibuat.</p>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Masa Berlaku Baru Sampai <span class="text-rose-500">*</span></label>
                        <input type="date" wire:model="renewExpiresOn" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" required />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Biaya yang Dibayar (Rp)</label>
                            <input type="number" wire:model="renewCostPaid" placeholder="0" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Estimasi Periode Depan (Rp)</label>
                            <input type="number" wire:model="newRenewalCostEstimate" placeholder="mis. 350000" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white" />
                        </div>
                    </div>

                    @if(count($accountOptions) > 0)
                        <div>
                            <label class="block text-xs font-semibold text-stone-700 dark:text-stone-300 mb-1">Bayar Menggunakan Akun / Dompet</label>
                            <select wire:model="renewAccountId" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:outline-hidden dark:border-stone-700 dark:bg-stone-800 dark:text-white">
                                <option value="">Tidak Catat Transaksi Pengeluaran</option>
                                @foreach($accountOptions as $accId => $accName)
                                    <option value="{{ $accId }}">{{ $accName }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" id="renew_finance" wire:model="renewAddToFinance" class="h-4 w-4 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500" />
                        <label for="renew_finance" class="text-xs text-stone-700 dark:text-stone-300 cursor-pointer">
                            Masukkan estimasi perpanjangan berikutnya ke Rencana Pengeluaran (PlannedItem)
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button wire:click="$set('showRenewModal', false)" class="rounded-xl border border-stone-200 px-4 py-2 text-xs font-semibold text-stone-700 hover:bg-stone-50 dark:border-stone-800 dark:text-stone-300">
                        Batal
                    </button>
                    <button wire:click="saveRenewal" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-500">
                        Simpan Perpanjangan
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
