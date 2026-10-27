<div class="mx-auto max-w-2xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-[#263c32] dark:text-[#f3f1e8]">
                {{ $loanId ? 'Edit Pinjaman' : 'Tambah Pinjaman Baru' }}
            </h1>
            <p class="text-xs sm:text-sm text-current/60">Catat hutang atau piutang lengkap dengan jadwal seluruh cicilan otomatis</p>
        </div>

        <a href="{{ route('finance.loans.index') }}" class="text-xs font-bold text-current/60 hover:underline">
            &larr; Kembali
        </a>
    </div>

    <form wire:submit.prevent="saveLoan" class="space-y-5">
        <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-6 shadow-sm transition-colors dark:bg-[#1f2c25] space-y-4">
            {{-- Direction --}}
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-current/60 mb-2">Arah Pinjaman</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex cursor-pointer items-center justify-center rounded-2xl border p-3 font-bold text-xs transition-all {{ $direction === 'payable' ? 'border-rose-500 bg-rose-500/10 text-rose-700 dark:text-rose-300' : 'border-current/15 text-current/70' }}">
                        <input type="radio" wire:model.live="direction" value="payable" class="hidden" />
                        <span>Hutang (Kita Bayar Cicilan)</span>
                    </label>
                    <label class="flex cursor-pointer items-center justify-center rounded-2xl border p-3 font-bold text-xs transition-all {{ $direction === 'receivable' ? 'border-emerald-500 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' : 'border-current/15 text-current/70' }}">
                        <input type="radio" wire:model.live="direction" value="receivable" class="hidden" />
                        <span>Piutang (Kita Terima Cicilan)</span>
                    </label>
                </div>
            </div>

            {{-- Name & Counterparty --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-current/70">Nama Pinjaman</label>
                    <input type="text" wire:model="name" placeholder="mis. KPR BTN, Kredit Motor Vario"
                           class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" />
                    @error('name') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-current/70">Pihak / Pemberi / Penerima</label>
                    <input type="text" wire:model="counterparty" placeholder="mis. Bank BTN, BCA Finance, Budi"
                           class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" />
                    @error('counterparty') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Principal, Count, Installment Amount --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-bold text-current/70">Pokok Pinjaman (Rp)</label>
                    <input type="text" wire:model="principal" placeholder="mis. 150jt atau 150000000"
                           class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm font-bold focus:border-emerald-500 focus:outline-none" />
                    @error('principal') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-current/70">Jumlah Cicilan (Kali)</label>
                    <input type="number" min="1" max="600" wire:model.live="installment_count"
                           class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm font-bold focus:border-emerald-500 focus:outline-none" />
                    @error('installment_count') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-current/70">Nominal per Cicilan (Rp)</label>
                    <input type="text" wire:model.live.debounce.400ms="installment_amount" placeholder="mis. 1,5jt atau 1500000"
                           class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm font-bold focus:border-emerald-500 focus:outline-none" />
                    @error('installment_amount') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Total Payable Override --}}
            <div>
                <label class="block text-xs font-bold text-current/70">Total yang Harus Dibayar (Otomatis)</label>
                <input type="text" wire:model="total_payable" placeholder="default = nominal × jumlah kali"
                       class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm font-bold focus:border-emerald-500 focus:outline-none" />
                <p class="text-[11px] text-current/50 mt-0.5">Jika total cicilan berbeda (mis. cicilan terakhir menyerap selisih), sistem otomatis menghitung cicilan ke-n.</p>
                @error('total_payable') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            {{-- First Due Date & Frequency --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-current/70">Jatuh Tempo Cicilan Pertama</label>
                    <input type="date" wire:model.live="first_due_date"
                           class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" />
                    @error('first_due_date') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-current/70">Frekuensi</label>
                    <select wire:model="frequency"
                            class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        <option value="monthly">Bulanan (Tanggal yang sama tiap bulan)</option>
                        <option value="weekly">Mingguan</option>
                        <option value="once">Sekali Bayar</option>
                    </select>
                </div>
            </div>

            {{-- Paid before tracking --}}
            @if(!$loanId)
                <div class="rounded-2xl border border-current/10 bg-current/5 p-4">
                    <label class="block text-xs font-bold text-current/80">Cicilan Sudah Dibayar Sebelumnya (Pinjaman Lama)</label>
                    <input type="number" min="0" max="{{ $installment_count }}" wire:model.live="paid_before_count"
                           class="mt-1 w-32 rounded-xl border border-current/20 bg-transparent px-3 py-1.5 text-xs font-bold focus:border-emerald-500 focus:outline-none" />
                    <p class="text-[11px] text-current/50 mt-1">
                        Untuk pinjaman lama yang sudah berjalan: N cicilan pertama otomatis ditandai lunas tanpa membuat mutasi kas ganda.
                    </p>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" id="rec_disb" wire:model="record_disbursement" class="size-4 rounded accent-emerald-600" />
                    <label for="rec_disb" class="text-xs font-semibold text-current/80">
                        Catat pencairan pokok pinjaman ke rekening saat ini (Pemasukan/Pengeluaran Awal)
                    </label>
                </div>
            @endif

            {{-- Account & Category Defaults --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-current/70">Akun Pembayaran Default</label>
                    <select wire:model="account_id"
                            class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-current/70">Kategori</label>
                    <select wire:model="category_id"
                            class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-current/70">Catatan Bunga / Keterangan (Opsional)</label>
                <input type="text" wire:model="interest_note" placeholder="mis. Bunga flat 5% p.a., Floating tahun ke-3"
                       class="mt-1 w-full rounded-xl border border-current/20 bg-transparent px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none" />
            </div>
        </div>

        {{-- Real-Time Schedule Preview (US-FIN-06 AC2) --}}
        @if(!empty($schedulePreview))
            <div class="rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-sm transition-colors dark:bg-[#1f2c25]">
                <h3 class="text-sm font-bold text-[#263c32] dark:text-[#f3f1e8]">Pratinjau Jadwal Cicilan (Otomatis Dihitung)</h3>
                <p class="text-[11px] text-current/50">Menampilkan hingga 12 cicilan pertama yang akan dibentuk ke Rencana Bulanan:</p>

                <div class="mt-3 divide-y divide-current/5 text-xs max-h-56 overflow-y-auto">
                    @foreach($schedulePreview as $p)
                        <div class="flex items-center justify-between py-2">
                            <div class="flex items-center gap-2">
                                <span class="font-bold">Cicilan {{ $p['sequence'] }}/{{ $installment_count }}</span>
                                <span class="text-current/60">{{ $p['due_date']->translatedFormat('d M Y') }}</span>
                                @if($p['paid_before'])
                                    <span class="rounded bg-emerald-500/10 px-1.5 py-0.2 text-[9px] font-bold text-emerald-600">Sudah Lunas</span>
                                @endif
                            </div>
                            <div class="font-black text-rose-600 dark:text-rose-400">
                                <x-money :amount="$p['amount']" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="flex items-center justify-end gap-2 pt-2">
            <a href="{{ route('finance.loans.index') }}" class="rounded-xl px-4 py-2 text-xs font-bold text-current/70 hover:bg-current/10">
                Batal
            </a>
            <button type="submit"
                    class="rounded-xl bg-emerald-600 px-6 py-2.5 text-xs sm:text-sm font-bold text-white shadow-sm hover:bg-emerald-700 active:scale-95">
                Simpan & Bentuk Jadwal
            </button>
        </div>
    </form>
</div>
