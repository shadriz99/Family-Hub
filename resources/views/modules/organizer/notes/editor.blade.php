<div class="max-w-3xl mx-auto pb-16">
    <!-- Top Bar -->
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('organizer.notes.index') }}" class="min-h-[44px] inline-flex items-center text-sm font-semibold text-[#637067] dark:text-[#637067] hover:text-[#263c32] dark:hover:text-white gap-1">
            ← Semua Catatan
        </a>
        <div class="flex items-center gap-3">
            <span class="text-xs {{ $isSaved ? 'text-[#28543e] dark:text-[#bad6ae]' : 'text-amber-500' }}">
                {{ $isSaved ? 'Tersimpan' : 'Menyimpan perubahan...' }}
            </span>
            <button type="button" wire:click="save" class="px-4 py-2 bg-[#28543e] hover:opacity-90 text-white dark:bg-[#bad6ae] dark:text-[#151f1b] text-xs font-semibold rounded-xl shadow-sm transition">
                Simpan
            </button>
        </div>
    </div>

    <!-- Note Canvas Card -->
    @php
        $canvasBg = match ($color) {
            'yellow' => 'bg-amber-50/70 dark:bg-amber-950/20 border-amber-200 dark:border-amber-800/50',
            'green' => 'bg-[#28543e]/10/70 dark:bg-[#bad6ae]/15 border-[#28543e]/20 dark:border-[#bad6ae]/25',
            'blue' => 'bg-sky-50/70 dark:bg-sky-950/20 border-sky-200 dark:border-sky-800/50',
            'pink' => 'bg-rose-50/70 dark:bg-rose-950/20 border-rose-200 dark:border-rose-800/50',
            'purple' => 'bg-purple-50/70 dark:bg-purple-950/20 border-purple-200 dark:border-purple-800/50',
            default => 'bg-[#fffefa] dark:bg-[#1f2c25] border-current/10 ',
        };
    @endphp

    <div class="p-6 rounded-2xl border shadow-sm space-y-4 {{ $canvasBg }}">
        <!-- Options row: Type, Color, Pin, Private -->
        <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-black/5 dark:border-white/5 text-xs">
            <!-- Note Type -->
            <div class="flex items-center gap-1 bg-current/5 dark:bg-current/5 p-1 rounded-xl">
                <button type="button" wire:click="$set('type', 'text')" class="px-3 py-1 rounded-lg {{ $type === 'text' ? 'bg-[#fffefa] dark:bg-current/10 font-semibold shadow-xs' : 'text-[#637067] dark:text-[#637067]' }}">
                    <x-heroicon-o-document-text class="mr-1 inline-block size-4 align-text-bottom" /> Teks Bebas
                </button>
                <button type="button" wire:click="$set('type', 'checklist')" class="px-3 py-1 rounded-lg {{ $type === 'checklist' ? 'bg-[#fffefa] dark:bg-current/10 font-semibold shadow-xs' : 'text-[#637067] dark:text-[#637067]' }}">
                    <x-heroicon-o-check-circle class="mr-1 inline-block size-4 align-text-bottom" /> Checklist
                </button>
            </div>

            <!-- Color Palette -->
            <div class="flex items-center gap-1.5">
                @foreach (['default' => 'bg-[#fffefa] border-current/15', 'yellow' => 'bg-amber-200 border-amber-400', 'green' => 'bg-emerald-200 border-emerald-400', 'blue' => 'bg-sky-200 border-sky-400', 'pink' => 'bg-rose-200 border-rose-400', 'purple' => 'bg-purple-200 border-purple-400'] as $cKey => $cClass)
                    <button 
                        type="button" 
                        wire:click="$set('color', '{{ $cKey }}')" 
                        class="w-5 h-5 rounded-full border {{ $cClass }} {{ $color === $cKey ? 'ring-2 ring-[#28543e] dark:ring-[#bad6ae] scale-110' : '' }}"
                        title="{{ ucfirst($cKey) }}"
                    ></button>
                @endforeach
            </div>

            <!-- Badges Toggle: Pin & Private -->
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-1.5 cursor-pointer text-[#263c32] dark:text-[#b2beb2]">
                    <input type="checkbox" wire:model.live="is_pinned" class="w-4 h-4 rounded text-[#28543e] border-current/15" />
                    <span class='inline-flex items-center gap-1'><x-heroicon-o-bookmark class="size-4" /> Sematkan</span>
                </label>
                <label class="flex items-center gap-1.5 cursor-pointer text-[#ad3f45] dark:text-[#ffafb1]">
                    <input type="checkbox" wire:model.live="is_private" class="w-4 h-4 rounded text-[#ad3f45] border-current/15" />
                    <span class='inline-flex items-center gap-1'><x-heroicon-o-lock-closed class="size-4" /> Catatan Privat</span>
                </label>
            </div>
        </div>

        <!-- Title -->
        <div>
            <input 
                type="text" 
                wire:model.live.debounce.1500ms="title" 
                placeholder="Judul catatan..." 
                class="w-full text-xl font-bold bg-transparent border-0 focus:outline-none placeholder-[#637067]/70 dark:placeholder-[#b2beb2]/70 text-[#263c32] dark:text-[#f3f1e8]"
            />
        </div>

        <!-- Body / Items Editor -->
        @if ($type === 'text')
            <div>
                <textarea 
                    wire:model.live.debounce.1500ms="body" 
                    rows="12" 
                    placeholder="Mulai menulis catatan... (Mendukung Markdown: **tebal**, *miring*, - list, # judul)" 
                    class="w-full bg-transparent border-0 focus:outline-none placeholder-[#637067]/70 dark:placeholder-[#b2beb2]/70 text-[#263c32] dark:text-[#f3f1e8] text-sm leading-relaxed resize-y"
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
                        class="flex-1 px-3 py-2 bg-black/5 dark:bg-[#fffefa]/5 border border-black/10 dark:border-white/10 rounded-xl text-sm focus:outline-none text-[#263c32] dark:text-[#f3f1e8]"
                    />
                    <button type="submit" wire:loading.attr="disabled" x-bind:disabled="$store.network?.isOffline" class="px-3.5 py-2 bg-[#28543e] text-white dark:bg-[#bad6ae] text-xs font-semibold rounded-xl">
                        Tambah
                    </button>
                </form>

                <!-- List Items -->
                <div class="space-y-1.5 pt-2">
                    @foreach ($items as $index => $item)
                        <div class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-black/5 dark:hover:bg-[#fffefa]/5 group">
                            <input 
                                type="checkbox" 
                                wire:model.live="items.{{ $index }}.is_done" 
                                class="w-4 h-4 rounded text-[#28543e] border-current/15"
                            />
                            <input 
                                type="text" 
                                wire:model.live.debounce.1500ms="items.{{ $index }}.text" 
                                class="flex-1 bg-transparent border-0 text-sm focus:outline-none {{ !empty($item['is_done']) ? 'line-through text-[#637067] dark:text-[#637067]' : 'text-[#263c32] dark:text-[#f3f1e8]' }}"
                            />
                            <button type="button" wire:click="removeChecklistItem({{ $index }})" class="opacity-0 group-hover:opacity-100 text-[#637067] hover:text-rose-500 text-xs px-1">
                                <x-heroicon-o-x-mark class="size-4" />
                            </button>
                        </div>
                    @endforeach
                </div>

                @if (collect($items)->contains('is_done', true))
                    <div class="pt-2 text-right">
                        <button type="button" wire:click="clearCompletedItems" class="text-xs text-[#ad3f45] dark:text-[#ffafb1] hover:underline">
                            Hapus item yang dicentang
                        </button>
                    </div>
                @endif
            </div>
        @endif

        <!-- Labels Input -->
        <div class="pt-3 border-t border-black/5 dark:border-white/5">
            <label class="block text-xs font-medium text-[#637067] dark:text-[#637067] mb-1">
                Label (pisahkan dengan koma, maks 5 label):
            </label>
            <input 
                type="text" 
                wire:model.live.debounce.1500ms="labelsInput" 
                placeholder="cth: resep, keluarga, rencana" 
                class="w-full px-3 py-1.5 bg-black/5 dark:bg-[#fffefa]/5 border border-black/10 dark:border-white/10 rounded-xl text-xs focus:outline-none text-[#263c32] dark:text-[#f3f1e8]"
            />
        </div>
    </div>

    <!-- Concurrency / Conflict Resolution Dialog -->
    @if ($showConflictDialog)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-[#fffefa] dark:bg-[#1f2c25] rounded-2xl max-w-lg w-full p-6 shadow-xl space-y-4 border border-current/10">
                <div class="flex items-center gap-2 text-[#87571f]">
                    <x-heroicon-o-exclamation-triangle class="size-5 text-[#87571f] dark:text-[#edc48b]" />
                    <h3 class="text-lg font-bold">Konflik Perubahan Catatan</h3>
                </div>
                <p class="text-sm text-[#637067] dark:text-[#b2beb2]">
                    Catatan ini baru saja diubah oleh <strong>{{ $conflictEditorName }}</strong> ({{ $conflictUpdatedAt }}).
                </p>

                @if ($serverBody)
                    <div class="p-3 bg-current/5 dark:bg-[#1f2c25] rounded-xl text-xs max-h-40 overflow-y-auto border border-current/10">
                        <span class="font-semibold block mb-1 text-[#637067]">Versi terkini di server:</span>
                        <div class="whitespace-pre-line text-[#263c32] dark:text-[#b2beb2]">{{ $serverBody }}</div>
                    </div>
                @endif

                <div class="flex flex-col sm:flex-row items-center justify-end gap-2 pt-2">
                    <button type="button" wire:click="saveAsCopy" class="w-full sm:w-auto px-4 py-2 text-xs font-semibold text-[#263c32] dark:text-[#b2beb2] bg-current/5 dark:bg-current/10 rounded-xl hover:bg-current/10">
                        Simpan Sebagai Salinan
                    </button>
                    <button type="button" wire:click="overwriteConflict" class="w-full sm:w-auto px-4 py-2 text-xs font-semibold text-white bg-[#ad3f45] dark:bg-[#ffafb1] dark:text-[#151f1b] hover:bg-rose-700 rounded-xl">
                        Timpa dengan Versi Saya
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
