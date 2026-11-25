<div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('assets.documents') }}" class="text-xs text-[#637067] hover:text-[#263c32] dark:hover:text-white">
                    ← Kembali ke Dokumen
                </a>
                <span class="text-[#637067]">/</span>
                <span class="text-xs text-[#637067] dark:text-[#637067]">{{ $doc->type->label() }}</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">
                {{ $doc->title }}
            </h1>
            <div class="mt-1 flex items-center gap-2 text-xs">
                @if($doc->asset)
                    <a href="{{ route('assets.show', $doc->asset->id) }}" class="text-[#28543e] hover:underline">
                        <x-heroicon-o-cube class="inline-block size-3.5 align-text-bottom" /> {{ $doc->asset->name }}
                    </a>
                    <span>•</span>
                @endif
                <span class="rounded-md px-2 py-0.5 font-medium {{ $doc->status->value === 'superseded' ? 'bg-current/5 text-[#637067]' : ($doc->isExpired() ? 'bg-rose-100 text-[#ad3f45]' : 'bg-[#28543e]/15 text-[#28543e]') }}">
                    {{ $doc->status->label() }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($doc->status->value === 'active')
                <button wire:click="openRenewModal" class="inline-flex items-center rounded-xl bg-[#28543e] px-4 py-2 text-xs font-semibold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] shadow-xs hover:opacity-90">
                    <x-heroicon-o-arrow-path class="mr-1 inline-block size-4 align-text-bottom" /> Perpanjang Dokumen
                </button>
            @endif
            <a href="{{ route('assets.documents.edit', $doc->id) }}" class="inline-flex items-center rounded-xl border border-current/10 bg-[#fffefa] px-3.5 py-2 text-xs font-semibold text-[#263c32] shadow-xs hover:bg-current/5 dark:bg-[#1f2c25] dark:text-[#b2beb2]">
                <x-heroicon-o-pencil class="mr-1 inline-block size-4 align-text-bottom" /> Edit
            </a>
        </div>
    </div>

    {{-- Detail Card --}}
    <div class="rounded-2xl border border-current/10 bg-[#fffefa] p-5 shadow-xs dark:bg-[#1f2c25]">
        <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2 text-xs">
            <div>
                <dt class="text-[#637067] dark:text-[#637067]">Nomor Dokumen / Polis</dt>
                <dd class="font-mono text-sm font-semibold text-[#263c32] dark:text-[#f3f1e8] mt-0.5">{{ $doc->number ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-[#637067] dark:text-[#637067]">Instansi / Penerbit</dt>
                <dd class="text-sm font-medium text-[#263c32] dark:text-[#f3f1e8] mt-0.5">{{ $doc->issuer ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-[#637067] dark:text-[#637067]">Tanggal Terbit</dt>
                <dd class="text-[#263c32] dark:text-[#f3f1e8] mt-0.5">{{ $doc->issued_on ? $doc->issued_on->translatedFormat('d F Y') : '-' }}</dd>
            </div>
            <div>
                <dt class="text-[#637067] dark:text-[#637067]">Berlaku Sampai (Kedaluwarsa)</dt>
                <dd class="text-sm font-bold text-[#263c32] dark:text-[#f3f1e8] mt-0.5">
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
                <dt class="text-[#637067] dark:text-[#637067]">Estimasi Biaya Perpanjangan</dt>
                <dd class="text-[#263c32] dark:text-[#f3f1e8] mt-0.5">{{ $doc->renewal_cost_estimate ? 'Rp ' . number_format($doc->renewal_cost_estimate, 0, ',', '.') : '-' }}</dd>
            </div>
            <div>
                <dt class="text-[#637067] dark:text-[#637067]">Rencana Pengeluaran (Finance)</dt>
                <dd class="text-[#263c32] dark:text-[#f3f1e8] mt-0.5">{{ $doc->add_to_finance_plan ? 'Aktif terhubung ke PlannedItem' : 'Tidak aktif' }}</dd>
            </div>
        </dl>

        @if($doc->notes)
            <div class="mt-5 border-t border-current/10 pt-4 text-xs">
                <span class="text-[#637067] dark:text-[#637067] block mb-1">Catatan</span>
                <p class="text-[#263c32] dark:text-[#b2beb2] whitespace-pre-line">{{ $doc->notes }}</p>
            </div>
        @endif
    </div>

    {{-- Renew Modal (US-AST-03 AC3) --}}
    @if($showRenewModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-lg rounded-2xl bg-[#fffefa] p-6 shadow-xl dark:bg-[#1f2c25]">
                <h3 class="text-base font-bold text-[#263c32] dark:text-[#f3f1e8]">Perpanjang Dokumen: {{ $doc->title }}</h3>
                <p class="mt-1 text-xs text-[#637067]">Dokumen lama akan ditandai superseded dan dokumen baru masa berlaku baru dibuat.</p>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Masa Berlaku Baru Sampai <span class="text-rose-500">*</span></label>
                        <input type="date" wire:model="renewExpiresOn" class="w-full rounded-xl border border-current/10 bg-current/5 px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" required />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Biaya yang Dibayar (Rp)</label>
                            <input type="number" wire:model="renewCostPaid" placeholder="0" class="w-full rounded-xl border border-current/10 bg-current/5 px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Estimasi Periode Depan (Rp)</label>
                            <input type="number" wire:model="newRenewalCostEstimate" placeholder="mis. 350000" class="w-full rounded-xl border border-current/10 bg-current/5 px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]" />
                        </div>
                    </div>

                    @if(count($accountOptions) > 0)
                        <div>
                            <label class="block text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] mb-1">Bayar Menggunakan Akun / Dompet</label>
                            <select wire:model="renewAccountId" class="w-full rounded-xl border border-current/10 bg-current/5 px-3 py-2 text-sm text-[#263c32] focus:border-[#28543e] dark:focus:border-[#bad6ae] focus:outline-hidden dark:bg-current/5 dark:text-[#f3f1e8]">
                                <option value="">Tidak Catat Transaksi Pengeluaran</option>
                                @foreach($accountOptions as $accId => $accName)
                                    <option value="{{ $accId }}">{{ $accName }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" id="renew_finance" wire:model="renewAddToFinance" class="h-4 w-4 rounded border-current/15 text-[#28543e] focus:ring-[#28543e] dark:focus:ring-[#bad6ae]" />
                        <label for="renew_finance" class="text-xs text-[#263c32] dark:text-[#b2beb2] cursor-pointer">
                            Masukkan estimasi perpanjangan berikutnya ke Rencana Pengeluaran (PlannedItem)
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button wire:click="$set('showRenewModal', false)" class="rounded-xl border border-current/10 px-4 py-2 text-xs font-semibold text-[#263c32] hover:bg-current/5 dark:text-[#b2beb2]">
                        Batal
                    </button>
                    <button wire:click="saveRenewal" class="rounded-xl bg-[#28543e] px-4 py-2 text-xs font-semibold text-white dark:bg-[#bad6ae] dark:text-[#151f1b] hover:opacity-90">
                        Simpan Perpanjangan
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
