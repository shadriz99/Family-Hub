<div class="max-w-2xl mx-auto pb-16">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">
                {{ $taskId ? 'Edit Tugas' : 'Tambah Tugas Baru' }}
            </h1>
            <p class="text-sm text-[#637067] dark:text-[#637067]">Tentukan penanggung jawab, jadwal, dan checklist sub-tugas.</p>
        </div>
        <a href="{{ route('organizer.tasks.index') }}" class="min-h-[44px] inline-flex items-center text-sm font-medium text-[#637067] dark:text-[#637067] hover:text-[#263c32] dark:hover:text-white">
            ← Kembali
        </a>
    </div>

    <form wire:submit.prevent="save" class="bg-[#fffefa] dark:bg-[#1f2c25] p-6 rounded-2xl shadow-sm border border-current/10 space-y-5">
        <!-- Title -->
        <div>
            <label class="block text-sm font-semibold text-[#263c32] dark:text-[#f3f1e8] mb-1">
                Judul Tugas <span class="text-rose-500">*</span>
            </label>
            <input 
                type="text" 
                wire:model="title" 
                placeholder="Misal: Kuras toren air, Belanja bulanan..." 
                class="w-full px-4 py-2.5 bg-current/5 dark:bg-current/5 border border-current/10 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#28543e] dark:focus:ring-[#bad6ae] text-[#263c32] dark:text-[#f3f1e8]"
                required
            />
            @error('title') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
        </div>

        <!-- Description -->
        <div>
            <label class="block text-sm font-semibold text-[#263c32] dark:text-[#f3f1e8] mb-1">
                Deskripsi / Catatan Tambahan
            </label>
            <textarea 
                wire:model="description" 
                rows="2"
                placeholder="Detail pengerjaan..." 
                class="w-full px-4 py-2 bg-current/5 dark:bg-current/5 border border-current/10 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#28543e] dark:focus:ring-[#bad6ae] text-[#263c32] dark:text-[#f3f1e8]"
            ></textarea>
        </div>

        <!-- Assignee & Priority -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-[#263c32] dark:text-[#f3f1e8] mb-1">
                    Penanggung Jawab
                </label>
                <select wire:model="assignee_user_id" class="w-full px-3 py-2 bg-current/5 dark:bg-current/5 border border-current/10 rounded-xl text-sm text-[#263c32] dark:text-[#f3f1e8]">
                    <option value="">Siapa saja</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->user_id }}">{{ $member->user->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-[#263c32] dark:text-[#f3f1e8] mb-1">
                    Prioritas
                </label>
                <select wire:model="priority" class="w-full px-3 py-2 bg-current/5 dark:bg-current/5 border border-current/10 rounded-xl text-sm text-[#263c32] dark:text-[#f3f1e8]">
                    <option value="low">Rendah</option>
                    <option value="normal">Normal</option>
                    <option value="high">Tinggi</option>
                </select>
            </div>
        </div>

        <!-- Due Date & Time -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-[#263c32] dark:text-[#f3f1e8] mb-1">
                    Tanggal Jatuh Tempo
                </label>
                <input 
                    type="date" 
                    wire:model="due_date" 
                    class="w-full px-3 py-2 bg-current/5 dark:bg-current/5 border border-current/10 rounded-xl text-sm text-[#263c32] dark:text-[#f3f1e8]"
                />
            </div>

            <div>
                <label class="block text-sm font-semibold text-[#263c32] dark:text-[#f3f1e8] mb-1">
                    Jam (Opsional)
                </label>
                <input 
                    type="time" 
                    wire:model="due_time" 
                    class="w-full px-3 py-2 bg-current/5 dark:bg-current/5 border border-current/10 rounded-xl text-sm text-[#263c32] dark:text-[#f3f1e8]"
                />
            </div>
        </div>

        <!-- Recurrence & Rotation -->
        <div class="p-4 bg-current/5 dark:bg-[#1f2c25]/30 rounded-xl border border-current/10 space-y-3">
            <div>
                <label class="block text-sm font-semibold text-[#263c32] dark:text-[#f3f1e8] mb-1">
                    Pengulangan Rutin
                </label>
                <select wire:model.live="recurrencePreset" class="w-full px-3 py-2 bg-[#fffefa] dark:bg-[#1f2c25] border border-current/10 rounded-xl text-sm text-[#263c32] dark:text-[#f3f1e8]">
                    <option value="none">Sekali Saja (Tidak Berulang)</option>
                    <option value="daily">Setiap Hari</option>
                    <option value="weekly">Setiap Minggu</option>
                    <option value="monthly">Setiap Bulan</option>
                    <option value="yearly">Setiap Tahun</option>
                    <option value="custom">Kustom (RRULE)</option>
                </select>
            </div>

            @if ($recurrencePreset === 'custom')
                <div>
                    <label class="block text-xs font-semibold text-[#637067] dark:text-[#637067] mb-1">String RFC 5545 RRULE</label>
                    <input type="text" wire:model="customRrule" placeholder="cth: FREQ=WEEKLY;BYDAY=SA,SU" class="w-full px-3 py-1.5 border rounded-lg text-sm dark:bg-[#1f2c25]" />
                </div>
            @endif

            @if ($recurrencePreset !== 'none')
                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" id="rotate" wire:model="rotate_assignee" class="w-4 h-4 rounded text-[#28543e] border-current/15" />
                    <label for="rotate" class="text-sm font-medium text-[#263c32] dark:text-[#b2beb2] cursor-pointer">
                        <x-heroicon-o-arrow-path class="mr-1 inline-block size-4 align-text-bottom" /> Rotasi penanggung jawab bergantian setiap jadwal berikutnya
                    </label>
                </div>
            @endif
        </div>

        <!-- Reminder Offset -->
        <div>
            <label class="block text-sm font-semibold text-[#263c32] dark:text-[#f3f1e8] mb-1">
                Pengingat
            </label>
            <select wire:model="reminder_offset" class="w-full px-3 py-2 bg-current/5 dark:bg-current/5 border border-current/10 rounded-xl text-sm text-[#263c32] dark:text-[#f3f1e8]">
                <option value="">Tidak ada pengingat</option>
                <option value="at_due">Saat jatuh tempo</option>
                <option value="1h_before">1 jam sebelum</option>
                <option value="1d_before_0800">1 hari sebelum (pukul 08:00)</option>
            </select>
        </div>

        <!-- Checklist Sub-items -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <label class="block text-sm font-semibold text-[#263c32] dark:text-[#f3f1e8]">
                    Checklist Sub-tugas
                </label>
                <button type="button" wire:click="addItem" class="text-xs font-semibold text-[#28543e] dark:text-[#bad6ae] hover:underline">
                    + Tambah Item
                </button>
            </div>

            @foreach ($items as $index => $item)
                <div class="flex items-center gap-2">
                    <input 
                        type="text" 
                        wire:model="items.{{ $index }}.text" 
                        placeholder="Langkah sub-tugas..." 
                        class="flex-1 px-3 py-1.5 bg-current/5 dark:bg-current/5 border border-current/10 rounded-lg text-sm text-[#263c32] dark:text-[#f3f1e8]"
                    />
                    <button type="button" wire:click="removeItem({{ $index }})" class="text-[#637067] hover:text-rose-500 p-1">
                        <x-heroicon-o-x-mark class="size-4" />
                    </button>
                </div>
            @endforeach
        </div>

        <!-- Expense On Complete Toggle -->
        <div class="p-4 bg-amber-50/50 dark:bg-amber-950/20 rounded-xl border border-amber-200 dark:border-amber-800/50 space-y-3">
            <div class="flex items-center gap-2">
                <input type="checkbox" id="expense_toggle" wire:model.live="expense_on_complete" class="w-4 h-4 rounded text-[#87571f] border-amber-300" />
                <label for="expense_toggle" class="text-sm font-semibold text-amber-900 dark:text-amber-300 cursor-pointer">
                    <x-heroicon-o-banknotes class="mr-1 inline-block size-4 align-text-bottom" /> Catat pengeluaran saat tugas diselesaikan
                </label>
            </div>

            @if ($expense_on_complete)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                    <div>
                        <label class="block text-xs font-medium text-[#263c32] dark:text-[#b2beb2] mb-1">Estimasi Biaya (Rp)</label>
                        <input type="number" wire:model="expense_estimate" placeholder="cth: 150000" class="w-full px-3 py-1.5 border rounded-lg text-sm dark:bg-[#1f2c25] text-[#263c32] dark:text-[#f3f1e8]" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-[#263c32] dark:text-[#b2beb2] mb-1">Kategori Pengeluaran</label>
                        <select wire:model="expense_category_id" class="w-full px-3 py-1.5 border rounded-lg text-sm dark:bg-[#1f2c25] text-[#263c32] dark:text-[#f3f1e8]">
                            <option value="">Pilih Kategori</option>
                            @foreach ($categories as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-current/10">
            <a href="{{ route('organizer.tasks.index') }}" class="min-h-[44px] inline-flex items-center px-4 py-2 text-sm text-[#637067] dark:text-[#637067] hover:bg-current/5 rounded-xl">
                Batal
            </a>
            <button type="submit" wire:loading.attr="disabled" x-bind:disabled="$store.network?.isOffline" class="min-h-[44px] px-5 py-2.5 bg-[#28543e] hover:opacity-90 text-white dark:bg-[#bad6ae] dark:text-[#151f1b] text-sm font-semibold rounded-xl shadow-sm transition">
                Simpan Tugas
            </button>
        </div>
    </form>
</div>
