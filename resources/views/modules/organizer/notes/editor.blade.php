<div class="max-w-3xl mx-auto pb-16">
    <!-- Top Bar -->
    <div class="mb-4 flex items-center justify-between">
        <a href="/organizer/notes" class="text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white flex items-center gap-1">
            ← Semua Catatan
        </a>
        <div class="flex items-center gap-3">
            <span class="text-xs {{ $isSaved ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-500' }}">
                {{ $isSaved ? '✓ Tersimpan' : '• Menyimpan perubahan...' }}
            </span>
            <button type="button" wire:click="save" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-sm transition">
                Simpan
            </button>
        </div>
    </div>

    <!-- Note Canvas Card -->
    @php
        $canvasBg = match ($color) {
            'yellow' => 'bg-amber-50/70 dark:bg-amber-950/20 border-amber-200 dark:border-amber-800/50',
            'green' => 'bg-emerald-50/70 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-800/50',
            'blue' => 'bg-sky-50/70 dark:bg-sky-950/20 border-sky-200 dark:border-sky-800/50',
            'pink' => 'bg-rose-50/70 dark:bg-rose-950/20 border-rose-200 dark:border-rose-800/50',
            'purple' => 'bg-purple-50/70 dark:bg-purple-950/20 border-purple-200 dark:border-purple-800/50',
            default => 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700',
        };
    @endphp

    <div class="p-6 rounded-2xl border shadow-sm space-y-4 {{ $canvasBg }}">
        <!-- Options row: Type, Color, Pin, Private -->
        <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-black/5 dark:border-white/5 text-xs">
            <!-- Note Type -->
            <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-900/50 p-1 rounded-xl">
                <button type="button" wire:click="$set('type', 'text')" class="px-3 py-1 rounded-lg {{ $type === 'text' ? 'bg-white dark:bg-slate-700 font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-400' }}">
                    📝 Teks Bebas
                </button>
                <button type="button" wire:click="$set('type', 'checklist')" class="px-3 py-1 rounded-lg {{ $type === 'checklist' ? 'bg-white dark:bg-slate-700 font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-400' }}">
                    ☑️ Checklist
                </button>
            </div>

            <!-- Color Palette -->
            <div class="flex items-center gap-1.5">
                @foreach (['default' => 'bg-white border-slate-300', 'yellow' => 'bg-amber-200 border-amber-400', 'green' => 'bg-emerald-200 border-emerald-400', 'blue' => 'bg-sky-200 border-sky-400', 'pink' => 'bg-rose-200 border-rose-400', 'purple' => 'bg-purple-200 border-purple-400'] as $cKey => $cClass)
                    <button 
                        type="button" 
                        wire:click="$set('color', '{{ $cKey }}')" 
                        class="w-5 h-5 rounded-full border {{ $cClass }} {{ $color === $cKey ? 'ring-2 ring-indigo-500 scale-110' : '' }}"
                        title="{{ ucfirst($cKey) }}"
                    ></button>
                @endforeach
            </div>

            <!-- Badges Toggle: Pin & Private -->
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-1.5 cursor-pointer text-slate-700 dark:text-slate-300">
                    <input type="checkbox" wire:model.live="is_pinned" class="w-4 h-4 rounded text-indigo-600 border-slate-300" />
                    <span>📌 Sematkan</span>
                </label>
                <label class="flex items-center gap-1.5 cursor-pointer text-rose-700 dark:text-rose-400">
                    <input type="checkbox" wire:model.live="is_private" class="w-4 h-4 rounded text-rose-600 border-slate-300" />
                    <span>🔒 Catatan Privat</span>
                </label>
            </div>
        </div>

        <!-- Title -->
        <div>
            <input 
                type="text" 
                wire:model.live.debounce.1500ms="title" 
                placeholder="Judul catatan..." 
                class="w-full text-xl font-bold bg-transparent border-0 focus:outline-none placeholder-slate-400 text-slate-900 dark:text-white"
            />
        </div>

        <!-- Body / Items Editor -->
        @if ($type === 'text')
            <div>
                <textarea 
                    wire:model.live.debounce.1500ms="body" 
                    rows="12" 
                    placeholder="Mulai menulis catatan... (Mendukung Markdown: **tebal**, *miring*, - list, # judul)" 
                    class="w-full bg-transparent border-0 focus:outline-none placeholder-slate-400 text-slate-800 dark:text-slate-200 text-sm leading-relaxed resize-y"
                ></textarea>
            </div>
        @else
            <!-- Checklist Mode -->
            <div class="space-y-2 pt-1">
                <!-- Add New Item -->
                <form wire:submit.prevent="addChecklistItem" class="flex items-center gap-2">
                    <input 
                        type="text" 
                        wire:model="newItemText" 
                        placeholder="+ Ketik item baru lalu tekan Enter..." 
                        class="flex-1 px-3 py-2 bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-xl text-sm focus:outline-none text-slate-900 dark:text-white"
                    />
                    <button type="submit" class="px-3.5 py-2 bg-slate-900 text-white dark:bg-indigo-600 text-xs font-semibold rounded-xl">
                        Tambah
                    </button>
                </form>

                <!-- List Items -->
                <div class="space-y-1.5 pt-2">
                    @foreach ($items as $index => $item)
                        <div class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-black/5 dark:hover:bg-white/5 group">
                            <input 
                                type="checkbox" 
                                wire:model.live="items.{{ $index }}.is_done" 
                                class="w-4 h-4 rounded text-indigo-600 border-slate-300 dark:border-slate-600"
                            />
                            <input 
                                type="text" 
                                wire:model.live.debounce.1500ms="items.{{ $index }}.text" 
                                class="flex-1 bg-transparent border-0 text-sm focus:outline-none {{ !empty($item['is_done']) ? 'line-through text-slate-400 dark:text-slate-500' : 'text-slate-800 dark:text-slate-200' }}"
                            />
                            <button type="button" wire:click="removeChecklistItem({{ $index }})" class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-rose-500 text-xs px-1">
                                ✕
                            </button>
                        </div>
                    @endforeach
                </div>

                @if (collect($items)->contains('is_done', true))
                    <div class="pt-2 text-right">
                        <button type="button" wire:click="clearCompletedItems" class="text-xs text-rose-600 dark:text-rose-400 hover:underline">
                            Hapus item yang dicentang
                        </button>
                    </div>
                @endif
            </div>
        @endif

        <!-- Labels Input -->
        <div class="pt-3 border-t border-black/5 dark:border-white/5">
            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
                Label (pisahkan dengan koma, maks 5 label):
            </label>
            <input 
                type="text" 
                wire:model.live.debounce.1500ms="labelsInput" 
                placeholder="cth: resep, keluarga, rencana" 
                class="w-full px-3 py-1.5 bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 rounded-xl text-xs focus:outline-none text-slate-900 dark:text-white"
            />
        </div>
    </div>

    <!-- Concurrency / Conflict Resolution Dialog -->
    @if ($showConflictDialog)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-4 border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-2 text-amber-600">
                    <span class="text-xl">⚠️</span>
                    <h3 class="text-lg font-bold">Konflik Perubahan Catatan</h3>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Catatan ini baru saja diubah oleh <strong>{{ $conflictEditorName }}</strong> ({{ $conflictUpdatedAt }}).
                </p>

                @if ($serverBody)
                    <div class="p-3 bg-slate-50 dark:bg-slate-900 rounded-xl text-xs max-h-40 overflow-y-auto border border-slate-200 dark:border-slate-700">
                        <span class="font-semibold block mb-1 text-slate-500">Versi terkini di server:</span>
                        <div class="whitespace-pre-line text-slate-700 dark:text-slate-300">{{ $serverBody }}</div>
                    </div>
                @endif

                <div class="flex flex-col sm:flex-row items-center justify-end gap-2 pt-2">
                    <button type="button" wire:click="saveAsCopy" class="w-full sm:w-auto px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 rounded-xl hover:bg-slate-200">
                        Simpan Sebagai Salinan
                    </button>
                    <button type="button" wire:click="overwriteConflict" class="w-full sm:w-auto px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl">
                        Timpa dengan Versi Saya
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
