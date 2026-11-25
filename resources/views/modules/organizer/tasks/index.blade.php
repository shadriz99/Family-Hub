<div class="space-y-6 max-w-4xl mx-auto pb-16">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">Tugas & Pekerjaan Rumah</h1>
            <p class="text-sm text-[#637067] dark:text-[#637067]">Kelola dan bagi tugas bersama pasangan secara rapi.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('organizer.tasks.create') }}" class="inline-flex min-h-[44px] items-center gap-2 px-4 py-2 bg-[#28543e] hover:opacity-90 text-white dark:bg-[#bad6ae] dark:text-[#151f1b] text-sm font-semibold rounded-xl shadow-sm transition">
                <x-heroicon-o-plus class="size-4" />
                Tambah Tugas
            </a>
        </div>
    </div>

    <!-- Quick Add Form -->
    <div class="bg-[#fffefa] dark:bg-[#1f2c25] p-4 rounded-2xl shadow-sm border border-current/10 space-y-3">
        <form wire:submit.prevent="quickAdd" class="flex flex-col sm:flex-row gap-2">
            <input 
                type="text" 
                wire:model="quickTitle" 
                placeholder="Tambah tugas cepat... (cth: Cuci filter AC)" 
                class="flex-1 px-4 py-2.5 bg-current/5 dark:bg-current/5 border border-current/10 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#28543e] dark:focus:ring-[#bad6ae] text-[#263c32] dark:text-[#f3f1e8]"
                required
            />
            <button type="submit" class="px-5 py-2.5 bg-[#28543e] hover:bg-black dark:bg-[#28543e] dark:hover:opacity-90 text-white dark:bg-[#bad6ae] dark:text-[#151f1b] text-sm font-semibold rounded-xl transition">
                Simpan
            </button>
        </form>

        <div class="flex flex-wrap items-center gap-2 pt-1 text-xs">
            <span class="text-[#637067] font-medium">Kapan:</span>
            <button type="button" wire:click="$set('quickChipDate', 'today')" class="px-2.5 py-1 rounded-lg border {{ $quickChipDate === 'today' ? 'bg-[#28543e]/10 dark:bg-[#bad6ae]/15 border-[#28543e] dark:border-[#bad6ae] text-[#28543e] dark:text-[#bad6ae] font-semibold' : 'border-current/10 text-[#637067] dark:text-[#637067]' }}">
                Hari ini
            </button>
            <button type="button" wire:click="$set('quickChipDate', 'tomorrow')" class="px-2.5 py-1 rounded-lg border {{ $quickChipDate === 'tomorrow' ? 'bg-[#28543e]/10 dark:bg-[#bad6ae]/15 border-[#28543e] dark:border-[#bad6ae] text-[#28543e] dark:text-[#bad6ae] font-semibold' : 'border-current/10 text-[#637067] dark:text-[#637067]' }}">
                Besok
            </button>
            <button type="button" wire:click="$set('quickChipDate', 'weekend')" class="px-2.5 py-1 rounded-lg border {{ $quickChipDate === 'weekend' ? 'bg-[#28543e]/10 dark:bg-[#bad6ae]/15 border-[#28543e] dark:border-[#bad6ae] text-[#28543e] dark:text-[#bad6ae] font-semibold' : 'border-current/10 text-[#637067] dark:text-[#637067]' }}">
                Akhir pekan
            </button>
            <span class="text-[#637067] dark:text-[#637067]">|</span>
            <span class="text-[#637067] font-medium">Untuk:</span>
            <button type="button" wire:click="$set('quickChipAssignee', 'me')" class="px-2.5 py-1 rounded-lg border {{ $quickChipAssignee === 'me' ? 'bg-[#28543e]/10 dark:bg-[#bad6ae]/15 border-[#28543e] dark:border-[#bad6ae] text-[#28543e] dark:text-[#bad6ae] font-semibold' : 'border-current/10 text-[#637067] dark:text-[#637067]' }}">
                Saya
            </button>
            <button type="button" wire:click="$set('quickChipAssignee', 'partner')" class="px-2.5 py-1 rounded-lg border {{ $quickChipAssignee === 'partner' ? 'bg-[#28543e]/10 dark:bg-[#bad6ae]/15 border-[#28543e] dark:border-[#bad6ae] text-[#28543e] dark:text-[#bad6ae] font-semibold' : 'border-current/10 text-[#637067] dark:text-[#637067]' }}">
                Pasangan
            </button>
        </div>
    </div>

    <!-- Tabs & Filter Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-current/10 pb-3">
        <!-- Tabs -->
        <div class="flex items-center gap-1 overflow-x-auto pb-1 sm:pb-0">
            <button wire:click="$set('tab', 'today')" class="px-3.5 py-1.5 rounded-lg text-sm font-semibold transition {{ $tab === 'today' ? 'bg-[#28543e]/15 dark:bg-[#bad6ae]/20 text-[#28543e] dark:text-[#bad6ae]' : 'text-[#637067] dark:text-[#637067] hover:bg-current/5 ' }}">
                Hari ini
            </button>
            <button wire:click="$set('tab', 'upcoming')" class="px-3.5 py-1.5 rounded-lg text-sm font-semibold transition {{ $tab === 'upcoming' ? 'bg-[#28543e]/15 dark:bg-[#bad6ae]/20 text-[#28543e] dark:text-[#bad6ae]' : 'text-[#637067] dark:text-[#637067] hover:bg-current/5 ' }}">
                Mendatang
            </button>
            <button wire:click="$set('tab', 'no_date')" class="px-3.5 py-1.5 rounded-lg text-sm font-semibold transition {{ $tab === 'no_date' ? 'bg-[#28543e]/15 dark:bg-[#bad6ae]/20 text-[#28543e] dark:text-[#bad6ae]' : 'text-[#637067] dark:text-[#637067] hover:bg-current/5 ' }}">
                Tanpa Tanggal
            </button>
            <button wire:click="$set('tab', 'completed')" class="px-3.5 py-1.5 rounded-lg text-sm font-semibold transition {{ $tab === 'completed' ? 'bg-[#28543e]/15 dark:bg-[#bad6ae]/20 text-[#28543e] dark:text-[#bad6ae]' : 'text-[#637067] dark:text-[#637067] hover:bg-current/5 ' }}">
                Selesai (30 Hari)
            </button>
        </div>

        <!-- Filter Person -->
        <div class="flex items-center gap-1 bg-current/5 dark:bg-[#1f2c25] p-1 rounded-xl text-xs font-medium text-[#637067] dark:text-[#b2beb2] self-start sm:self-auto">
            <button wire:click="$set('assigneeFilter', 'all')" class="px-3 py-1 rounded-lg {{ $assigneeFilter === 'all' ? 'bg-[#fffefa] dark:bg-current/10 shadow-sm font-semibold text-[#263c32] dark:text-[#f3f1e8]' : '' }}">
                Semua
            </button>
            <button wire:click="$set('assigneeFilter', 'me')" class="px-3 py-1 rounded-lg {{ $assigneeFilter === 'me' ? 'bg-[#fffefa] dark:bg-current/10 shadow-sm font-semibold text-[#263c32] dark:text-[#f3f1e8]' : '' }}">
                Saya
            </button>
            <button wire:click="$set('assigneeFilter', 'partner')" class="px-3 py-1 rounded-lg {{ $assigneeFilter === 'partner' ? 'bg-[#fffefa] dark:bg-current/10 shadow-sm font-semibold text-[#263c32] dark:text-[#f3f1e8]' : '' }}">
                Pasangan
            </button>
        </div>
    </div>

    <!-- Undo Toast Notification -->
    @if ($undoMessage)
        <div class="flex items-center justify-between p-3.5 bg-[#28543e]/10 dark:bg-[#bad6ae]/15 border border-[#28543e]/30 dark:border-[#bad6ae]/30 rounded-xl text-[#28543e] dark:text-[#bad6ae] text-sm">
            <span>{{ $undoMessage }}</span>
            <button wire:click="undoComplete" class="font-semibold underline hover:no-underline text-[#28543e] dark:text-[#bad6ae] ml-4">
                Batalkan
            </button>
        </div>
    @endif

    <!-- Task List -->
    <div class="space-y-3">
        @forelse ($tasks as $task)
            <div class="bg-[#fffefa] dark:bg-[#1f2c25] rounded-2xl p-4 shadow-sm border border-current/10 transition hover:border-current/25">
                <div class="flex items-start gap-3">
                    <!-- Checkbox 44px touch area -->
                    <button 
                        type="button" 
                        wire:click="toggleComplete('{{ $task->id }}')" 
                        class="w-11 h-11 flex-shrink-0 flex items-center justify-center rounded-xl border-2 transition {{ $task->status->value === 'done' ? 'bg-[#28543e] dark:bg-[#bad6ae] border-[#28543e] dark:border-[#bad6ae] text-white' : 'border-current/15 hover:border-[#28543e] dark:border-[#bad6ae] text-transparent hover:text-[#637067]' }}"
                        title="{{ $task->status->value === 'done' ? 'Tandai belum selesai' : 'Tandai selesai' }}"
                    >
                        <svg class="w-6 h-6 stroke-current stroke-2 fill-none" viewBox="0 0 24 24">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                    </button>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-semibold text-[#263c32] dark:text-[#f3f1e8] {{ $task->status->value === 'done' ? 'line-through text-[#637067] dark:text-[#637067]' : '' }}">
                                {{ $task->title }}
                            </h3>

                            @if ($task->priority->value === 'high')
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-md bg-rose-100 text-[#ad3f45] dark:bg-rose-950/50 dark:text-[#ffafb1]">
                                    Tinggi
                                </span>
                            @endif

                            @if ($task->rrule)
                                <span class="px-2 py-0.5 text-xs font-medium rounded-md bg-[#28543e]/10 text-[#28543e] dark:bg-[#28543e]/10 dark:text-[#bad6ae] flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Berulang
                                </span>
                            @endif

                            @if ($task->expense_on_complete)
                                <span class="px-2 py-0.5 text-xs font-medium rounded-md bg-amber-50 text-[#87571f] dark:bg-amber-950/50 dark:text-[#edc48b] flex items-center gap-1">
                                    <x-heroicon-o-banknotes class="mr-1 inline-block size-4 align-text-bottom" /> Biaya @if($task->expense_estimate) Rp {{ number_format($task->expense_estimate, 0, ',', '.') }} @endif
                                </span>
                            @endif
                        </div>

                        @if ($task->description)
                            <p class="text-sm text-[#637067] dark:text-[#637067] mt-1 whitespace-pre-line">{{ $task->description }}</p>
                        @endif

                        <!-- Sub-items checklist -->
                        @if ($task->items->isNotEmpty())
                            <div class="mt-3 space-y-1.5 pl-1 border-l-2 border-current/10">
                                @foreach ($task->items as $item)
                                    <div class="flex items-center gap-2 text-sm text-[#263c32] dark:text-[#b2beb2]">
                                        <input 
                                            type="checkbox" 
                                            wire:click="toggleItem('{{ $item->id }}')" 
                                            {{ $item->is_done ? 'checked' : '' }} 
                                            class="w-4 h-4 rounded text-[#28543e] focus:ring-[#28543e] dark:focus:ring-[#bad6ae] border-current/15"
                                        />
                                        <span class="{{ $item->is_done ? 'line-through text-[#637067] dark:text-[#637067]' : '' }}">{{ $item->text }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Meta details -->
                        <div class="flex flex-wrap items-center gap-4 text-xs text-[#637067] dark:text-[#637067] mt-3 pt-2 border-t border-current/10 /50">
                            @if ($task->due_date)
                                <span class="flex items-center gap-1 {{ $task->due_date->toDateString() < $todayStr && $task->status->value === 'open' ? 'text-[#ad3f45] dark:text-[#ffafb1] font-semibold' : '' }}">
                                    <x-heroicon-o-calendar class="mr-1 inline-block size-4 align-text-bottom" /> {{ $task->due_date->format('d M Y') }}
                                    @if ($task->due_time) pukul {{ substr($task->due_time, 0, 5) }} @endif
                                </span>
                            @endif

                            @if ($task->assignee)
                                <span class="flex items-center gap-1 font-medium text-[#263c32] dark:text-[#b2beb2]">
                                    <x-heroicon-o-user class="mr-1 inline-block size-4 align-text-bottom" /> {{ $task->assignee->name }}
                                </span>
                            @else
                                <span class="text-[#637067]">Siapa saja</span>
                            @endif

                            @if ($task->rotate_assignee)
                                <span class="text-[#28543e] dark:text-[#bad6ae]"><x-heroicon-o-arrow-path class="mr-1 inline-block size-4 align-text-bottom" /> Bergantian</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-[#fffefa] dark:bg-[#1f2c25] rounded-2xl p-10 text-center border border-current/10">
                <p class="text-[#637067] dark:text-[#637067]">Tidak ada tugas pada tab ini.</p>
            </div>
        @endforelse
    </div>

    <!-- Expense Recording Modal -->
    @if ($showExpenseModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-[#fffefa] dark:bg-[#1f2c25] rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4 border border-current/10">
                <h3 class="text-lg font-bold text-[#263c32] dark:text-[#f3f1e8]">Catat Pengeluaran Tugas</h3>
                <p class="text-sm text-[#637067] dark:text-[#b2beb2]">Tugas ini ditandai selesai. Masukkan detail pengeluaran untuk dicatat otomatis ke modul Keuangan.</p>

                <div class="space-y-3 text-sm">
                    <div>
                        <label class="block font-medium text-[#263c32] dark:text-[#b2beb2] mb-1">Nominal (Rp)</label>
                        <input type="number" wire:model="expenseAmount" class="w-full px-3 py-2 border rounded-xl dark:bg-[#1f2c25] border-current/10 text-[#263c32] dark:text-[#f3f1e8]" required />
                    </div>

                    <div>
                        <label class="block font-medium text-[#263c32] dark:text-[#b2beb2] mb-1">Akun Pembayaran</label>
                        <select wire:model="expenseAccountId" class="w-full px-3 py-2 border rounded-xl dark:bg-[#1f2c25] border-current/10 text-[#263c32] dark:text-[#f3f1e8]">
                            @foreach ($accounts as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-medium text-[#263c32] dark:text-[#b2beb2] mb-1">Kategori Keuangan</label>
                        <select wire:model="expenseCategoryId" class="w-full px-3 py-2 border rounded-xl dark:bg-[#1f2c25] border-current/10 text-[#263c32] dark:text-[#f3f1e8]">
                            <option value="">Pilih Kategori</option>
                            @foreach ($categories as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3">
                    <button type="button" wire:click="skipExpenseComplete" class="px-4 py-2 text-sm text-[#637067] dark:text-[#637067] hover:bg-current/5 rounded-xl">
                        Lewati
                    </button>
                    <button type="button" wire:click="confirmExpenseComplete" class="px-4 py-2 text-sm font-semibold text-white bg-[#28543e] dark:bg-[#bad6ae] hover:opacity-90 rounded-xl">
                        Simpan Transaksi
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
