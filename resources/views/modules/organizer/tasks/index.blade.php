<div class="space-y-6 max-w-4xl mx-auto pb-16">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Tugas & Pekerjaan Rumah</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Kelola dan bagi tugas bersama pasangan secara rapi.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/organizer/tasks/create" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Tugas
            </a>
        </div>
    </div>

    <!-- Quick Add Form -->
    <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 space-y-3">
        <form wire:submit.prevent="quickAdd" class="flex flex-col sm:flex-row gap-2">
            <input 
                type="text" 
                wire:model="quickTitle" 
                placeholder="Tambah tugas cepat... (cth: Cuci filter AC)" 
                class="flex-1 px-4 py-2.5 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white"
                required
            />
            <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-black dark:bg-indigo-600 dark:hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition">
                Simpan
            </button>
        </form>

        <div class="flex flex-wrap items-center gap-2 pt-1 text-xs">
            <span class="text-slate-400 font-medium">Kapan:</span>
            <button type="button" wire:click="$set('quickChipDate', 'today')" class="px-2.5 py-1 rounded-lg border {{ $quickChipDate === 'today' ? 'bg-indigo-50 dark:bg-indigo-950/40 border-indigo-500 text-indigo-600 dark:text-indigo-400 font-semibold' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400' }}">
                Hari ini
            </button>
            <button type="button" wire:click="$set('quickChipDate', 'tomorrow')" class="px-2.5 py-1 rounded-lg border {{ $quickChipDate === 'tomorrow' ? 'bg-indigo-50 dark:bg-indigo-950/40 border-indigo-500 text-indigo-600 dark:text-indigo-400 font-semibold' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400' }}">
                Besok
            </button>
            <button type="button" wire:click="$set('quickChipDate', 'weekend')" class="px-2.5 py-1 rounded-lg border {{ $quickChipDate === 'weekend' ? 'bg-indigo-50 dark:bg-indigo-950/40 border-indigo-500 text-indigo-600 dark:text-indigo-400 font-semibold' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400' }}">
                Akhir pekan
            </button>
            <span class="text-slate-300 dark:text-slate-600">|</span>
            <span class="text-slate-400 font-medium">Untuk:</span>
            <button type="button" wire:click="$set('quickChipAssignee', 'me')" class="px-2.5 py-1 rounded-lg border {{ $quickChipAssignee === 'me' ? 'bg-indigo-50 dark:bg-indigo-950/40 border-indigo-500 text-indigo-600 dark:text-indigo-400 font-semibold' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400' }}">
                Saya
            </button>
            <button type="button" wire:click="$set('quickChipAssignee', 'partner')" class="px-2.5 py-1 rounded-lg border {{ $quickChipAssignee === 'partner' ? 'bg-indigo-50 dark:bg-indigo-950/40 border-indigo-500 text-indigo-600 dark:text-indigo-400 font-semibold' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400' }}">
                Pasangan
            </button>
        </div>
    </div>

    <!-- Tabs & Filter Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 dark:border-slate-700 pb-3">
        <!-- Tabs -->
        <div class="flex items-center gap-1 overflow-x-auto pb-1 sm:pb-0">
            <button wire:click="$set('tab', 'today')" class="px-3.5 py-1.5 rounded-lg text-sm font-semibold transition {{ $tab === 'today' ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                Hari ini
            </button>
            <button wire:click="$set('tab', 'upcoming')" class="px-3.5 py-1.5 rounded-lg text-sm font-semibold transition {{ $tab === 'upcoming' ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                Mendatang
            </button>
            <button wire:click="$set('tab', 'no_date')" class="px-3.5 py-1.5 rounded-lg text-sm font-semibold transition {{ $tab === 'no_date' ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                Tanpa Tanggal
            </button>
            <button wire:click="$set('tab', 'completed')" class="px-3.5 py-1.5 rounded-lg text-sm font-semibold transition {{ $tab === 'completed' ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                Selesai (30 Hari)
            </button>
        </div>

        <!-- Filter Person -->
        <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl text-xs font-medium text-slate-600 dark:text-slate-300 self-start sm:self-auto">
            <button wire:click="$set('assigneeFilter', 'all')" class="px-3 py-1 rounded-lg {{ $assigneeFilter === 'all' ? 'bg-white dark:bg-slate-700 shadow-sm font-semibold text-slate-900 dark:text-white' : '' }}">
                Semua
            </button>
            <button wire:click="$set('assigneeFilter', 'me')" class="px-3 py-1 rounded-lg {{ $assigneeFilter === 'me' ? 'bg-white dark:bg-slate-700 shadow-sm font-semibold text-slate-900 dark:text-white' : '' }}">
                Saya
            </button>
            <button wire:click="$set('assigneeFilter', 'partner')" class="px-3 py-1 rounded-lg {{ $assigneeFilter === 'partner' ? 'bg-white dark:bg-slate-700 shadow-sm font-semibold text-slate-900 dark:text-white' : '' }}">
                Pasangan
            </button>
        </div>
    </div>

    <!-- Undo Toast Notification -->
    @if ($undoMessage)
        <div class="flex items-center justify-between p-3.5 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-300 dark:border-emerald-700 rounded-xl text-emerald-800 dark:text-emerald-200 text-sm">
            <span>{{ $undoMessage }}</span>
            <button wire:click="undoComplete" class="font-semibold underline hover:no-underline text-emerald-700 dark:text-emerald-300 ml-4">
                Batalkan
            </button>
        </div>
    @endif

    <!-- Task List -->
    <div class="space-y-3">
        @forelse ($tasks as $task)
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-200 dark:border-slate-700 transition hover:border-slate-300 dark:hover:border-slate-600">
                <div class="flex items-start gap-3">
                    <!-- Checkbox 44px touch area -->
                    <button 
                        type="button" 
                        wire:click="toggleComplete('{{ $task->id }}')" 
                        class="w-11 h-11 flex-shrink-0 flex items-center justify-center rounded-xl border-2 transition {{ $task->status->value === 'done' ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-300 dark:border-slate-600 hover:border-indigo-500 text-transparent hover:text-slate-300' }}"
                        title="{{ $task->status->value === 'done' ? 'Tandai belum selesai' : 'Tandai selesai' }}"
                    >
                        <svg class="w-6 h-6 stroke-current stroke-2 fill-none" viewBox="0 0 24 24">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                    </button>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white {{ $task->status->value === 'done' ? 'line-through text-slate-400 dark:text-slate-500' : '' }}">
                                {{ $task->title }}
                            </h3>

                            @if ($task->priority->value === 'high')
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-md bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400">
                                    Tinggi
                                </span>
                            @endif

                            @if ($task->rrule)
                                <span class="px-2 py-0.5 text-xs font-medium rounded-md bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-400 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Berulang
                                </span>
                            @endif

                            @if ($task->expense_on_complete)
                                <span class="px-2 py-0.5 text-xs font-medium rounded-md bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400 flex items-center gap-1">
                                    💰 Biaya @if($task->expense_estimate) Rp {{ number_format($task->expense_estimate, 0, ',', '.') }} @endif
                                </span>
                            @endif
                        </div>

                        @if ($task->description)
                            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 whitespace-pre-line">{{ $task->description }}</p>
                        @endif

                        <!-- Sub-items checklist -->
                        @if ($task->items->isNotEmpty())
                            <div class="mt-3 space-y-1.5 pl-1 border-l-2 border-slate-100 dark:border-slate-700">
                                @foreach ($task->items as $item)
                                    <div class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                                        <input 
                                            type="checkbox" 
                                            wire:click="toggleItem('{{ $item->id }}')" 
                                            {{ $item->is_done ? 'checked' : '' }} 
                                            class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 dark:border-slate-600"
                                        />
                                        <span class="{{ $item->is_done ? 'line-through text-slate-400 dark:text-slate-500' : '' }}">{{ $item->text }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Meta details -->
                        <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 dark:text-slate-400 mt-3 pt-2 border-t border-slate-100 dark:border-slate-700/50">
                            @if ($task->due_date)
                                <span class="flex items-center gap-1 {{ $task->due_date->toDateString() < $todayStr && $task->status->value === 'open' ? 'text-rose-600 dark:text-rose-400 font-semibold' : '' }}">
                                    📅 {{ $task->due_date->format('d M Y') }}
                                    @if ($task->due_time) pukul {{ substr($task->due_time, 0, 5) }} @endif
                                </span>
                            @endif

                            @if ($task->assignee)
                                <span class="flex items-center gap-1 font-medium text-slate-700 dark:text-slate-300">
                                    👤 {{ $task->assignee->name }}
                                </span>
                            @else
                                <span class="text-slate-400">Siapa saja</span>
                            @endif

                            @if ($task->rotate_assignee)
                                <span class="text-indigo-600 dark:text-indigo-400">🔄 Bergantian</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-10 text-center border border-slate-200 dark:border-slate-700">
                <p class="text-slate-500 dark:text-slate-400">Tidak ada tugas pada tab ini.</p>
            </div>
        @endforelse
    </div>

    <!-- Expense Recording Modal -->
    @if ($showExpenseModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4 border border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Catat Pengeluaran Tugas</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300">Tugas ini ditandai selesai. Masukkan detail pengeluaran untuk dicatat otomatis ke modul Keuangan.</p>

                <div class="space-y-3 text-sm">
                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Nominal (Rp)</label>
                        <input type="number" wire:model="expenseAmount" class="w-full px-3 py-2 border rounded-xl dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white" required />
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Akun Pembayaran</label>
                        <select wire:model="expenseAccountId" class="w-full px-3 py-2 border rounded-xl dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                            @foreach ($accounts as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Kategori Keuangan</label>
                        <select wire:model="expenseCategoryId" class="w-full px-3 py-2 border rounded-xl dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white">
                            <option value="">Pilih Kategori</option>
                            @foreach ($categories as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3">
                    <button type="button" wire:click="skipExpenseComplete" class="px-4 py-2 text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl">
                        Lewati
                    </button>
                    <button type="button" wire:click="confirmExpenseComplete" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl">
                        Simpan Transaksi
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
