<div class="space-y-6 max-w-6xl mx-auto pb-16">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">Catatan & Checklist</h1>
            <p class="text-sm text-[#637067] dark:text-[#637067]">Ide bersama, checklist persiapan, dan info penting keluarga.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('organizer.notes.create') }}" class="inline-flex min-h-[44px] items-center gap-2 px-4 py-2 bg-[#28543e] hover:opacity-90 text-white dark:bg-[#bad6ae] dark:text-[#151f1b] text-sm font-semibold rounded-xl shadow-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Buat Catatan Baru
            </a>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="flex flex-col sm:flex-row gap-3 items-center justify-between">
        <div class="w-full sm:w-80">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Cari judul, isi, atau checklist..." 
                class="w-full px-4 py-2 bg-[#fffefa] dark:bg-[#1f2c25] border border-current/10 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#28543e] dark:focus:ring-[#bad6ae] text-[#263c32] dark:text-[#f3f1e8]"
            />
        </div>

        <div class="flex items-center gap-1 bg-current/5 dark:bg-[#1f2c25] p-1 rounded-xl text-xs font-semibold text-[#637067] dark:text-[#b2beb2] w-full sm:w-auto overflow-x-auto">
            <button wire:click="$set('view', 'active')" class="px-3.5 py-1.5 rounded-lg {{ $view === 'active' ? 'bg-[#fffefa] dark:bg-current/10 shadow-sm text-[#263c32] dark:text-[#f3f1e8]' : '' }}">
                Catatan Aktif
            </button>
            <button wire:click="$set('view', 'archived')" class="px-3.5 py-1.5 rounded-lg {{ $view === 'archived' ? 'bg-[#fffefa] dark:bg-current/10 shadow-sm text-[#263c32] dark:text-[#f3f1e8]' : '' }}">
                Arsip
            </button>
            <button wire:click="$set('view', 'trash')" class="px-3.5 py-1.5 rounded-lg {{ $view === 'trash' ? 'bg-[#fffefa] dark:bg-current/10 shadow-sm text-[#ad3f45] dark:text-[#ffafb1]' : '' }}">
                Sampah
            </button>
        </div>
    </div>

    <!-- Label Chips -->
    @if (! empty($allLabels))
        <div class="flex flex-wrap items-center gap-1.5 text-xs">
            <span class="text-[#637067] font-medium mr-1">Label:</span>
            <button wire:click="$set('selectedLabel', '')" class="px-2.5 py-1 rounded-lg border {{ $selectedLabel === '' ? 'bg-[#28543e] text-white dark:bg-[#bad6ae] dark:text-[#151f1b] border-[#28543e] dark:border-[#bad6ae]' : 'bg-[#fffefa] dark:bg-[#1f2c25] border-current/10 text-[#637067] dark:text-[#b2beb2]' }}">
                Semua
            </button>
            @foreach ($allLabels as $lbl)
                <button wire:click="$set('selectedLabel', '{{ $lbl }}')" class="px-2.5 py-1 rounded-lg border {{ $selectedLabel === $lbl ? 'bg-[#28543e] text-white dark:bg-[#bad6ae] dark:text-[#151f1b] border-[#28543e] dark:border-[#bad6ae]' : 'bg-[#fffefa] dark:bg-[#1f2c25] border-current/10 text-[#637067] dark:text-[#b2beb2]' }}">
                    #{{ $lbl }}
                </button>
            @endforeach
        </div>
    @endif

    <!-- Notes Masonry Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($notes as $note)
            @php
                $colorClasses = match ($note->color) {
                    'yellow' => 'bg-amber-50/80 dark:bg-amber-950/30 border-amber-200 dark:border-amber-800/60',
                    'green' => 'bg-[#28543e]/10/80 dark:bg-[#bad6ae]/15 border-[#28543e]/20 dark:border-[#bad6ae]/25',
                    'blue' => 'bg-sky-50/80 dark:bg-sky-950/30 border-sky-200 dark:border-sky-800/60',
                    'pink' => 'bg-rose-50/80 dark:bg-rose-950/30 border-rose-200 dark:border-rose-800/60',
                    'purple' => 'bg-purple-50/80 dark:bg-purple-950/30 border-purple-200 dark:border-purple-800/60',
                    default => 'bg-[#fffefa] dark:bg-[#1f2c25] border-current/10 ',
                };
            @endphp
            <div class="rounded-2xl p-5 border shadow-sm flex flex-col justify-between transition hover:shadow-md relative group {{ $colorClasses }}">
                <div>
                    <!-- Top bar: pin & private badges -->
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            @if ($note->is_private)
                                <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-rose-100 text-[#ad3f45] dark:bg-rose-950/60 dark:text-rose-300">
                                    <x-heroicon-o-lock-closed class="mr-1 inline-block size-4 align-text-bottom" /> Privat
                                </span>
                            @endif
                            @if ($note->is_pinned)
                                <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-[#28543e]/15 text-[#28543e] dark:bg-[#bad6ae]/15 dark:text-[#bad6ae]">
                                    <x-heroicon-o-bookmark class="mr-1 inline-block size-4 align-text-bottom" /> Disematkan
                                </span>
                            @endif
                        </div>

                        <!-- Action controls -->
                        <div class="flex items-center gap-0.5 text-[#637067] group-hover:opacity-100 opacity-80 sm:opacity-60 transition">
                            @if ($view === 'active')
                                <button type="button" wire:click="togglePin('{{ $note->id }}')"
                                    aria-label="{{ $note->is_pinned ? 'Lepas Sematan' : 'Sematkan Catatan' }}"
                                    title="{{ $note->is_pinned ? 'Lepas Sematan' : 'Sematkan' }}"
                                    class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-xl hover:bg-[#28543e]/10 hover:text-[#28543e]">
                                    <x-heroicon-o-bookmark class="size-4 {{ $note->is_pinned ? 'fill-[#28543e] text-[#28543e] dark:fill-[#bad6ae] dark:text-[#bad6ae]' : '' }}" />
                                </button>
                                <button type="button" wire:click="archive('{{ $note->id }}')"
                                    aria-label="Arsipkan Catatan"
                                    title="Arsipkan"
                                    class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-xl hover:bg-amber-50 hover:text-[#87571f]">
                                    <x-heroicon-o-archive-box class="size-4" />
                                </button>
                                <button type="button" wire:click="delete('{{ $note->id }}')"
                                    aria-label="Pindahkan ke Sampah"
                                    title="Pindahkan ke sampah"
                                    class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-xl hover:bg-rose-50 hover:text-[#ad3f45]">
                                    <x-heroicon-o-trash class="size-4" />
                                </button>
                            @elseif ($view === 'archived')
                                <button type="button" wire:click="unarchive('{{ $note->id }}')"
                                    aria-label="Keluarkan dari Arsip"
                                    title="Keluarkan dari arsip"
                                    class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-xl hover:bg-[#28543e]/10 hover:text-[#28543e]">
                                    <x-heroicon-o-arrow-up-tray class="size-4" />
                                </button>
                                <button type="button" wire:click="delete('{{ $note->id }}')"
                                    aria-label="Pindahkan ke Sampah"
                                    title="Pindahkan ke sampah"
                                    class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-xl hover:bg-rose-50 hover:text-[#ad3f45]">
                                    <x-heroicon-o-trash class="size-4" />
                                </button>
                            @elseif ($view === 'trash')
                                <button type="button" wire:click="restore('{{ $note->id }}')"
                                    aria-label="Pulihkan Catatan"
                                    title="Pulihkan"
                                    class="inline-flex min-h-[44px] items-center justify-center px-3 py-1.5 text-xs font-semibold bg-[#28543e]/15 text-[#28543e] dark:bg-[#bad6ae]/15 dark:text-[#bad6ae] rounded-xl hover:bg-emerald-200">
                                    Pulihkan
                                </button>
                                <button type="button" wire:click="forceDeleteNote('{{ $note->id }}')"
                                    wire:confirm="Yakin ingin menghapus catatan ini secara permanen?"
                                    aria-label="Hapus Catatan Permanen"
                                    title="Hapus permanen"
                                    class="inline-flex min-h-[44px] items-center justify-center px-3 py-1.5 text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 rounded-xl hover:bg-rose-200">
                                    Hapus
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Title -->
                    <a href="{{ route('organizer.notes.edit', $note->id) }}" class="block">
                        <h3 class="text-base font-bold text-[#263c32] dark:text-[#f3f1e8] hover:text-[#28543e] dark:hover:text-[#bad6ae]">
                            {{ $note->title ?: 'Tanpa Judul' }}
                        </h3>
                    </a>

                    <!-- Body / Items -->
                    @if ($note->type->value === 'text' && $note->body)
                        <div class="mt-2 text-sm text-[#637067] dark:text-[#b2beb2] line-clamp-6 prose dark:prose-invert max-w-none text-xs">
                            {!! Str::markdown($note->body, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
                        </div>
                    @elseif ($note->type->value === 'checklist')
                        <div class="mt-2.5 space-y-1.5">
                            @foreach ($note->items->take(6) as $item)
                                <div class="flex items-center gap-2 text-xs text-[#263c32] dark:text-[#b2beb2]">
                                    <input 
                                        type="checkbox" 
                                        wire:click="toggleItem('{{ $item->id }}')" 
                                        {{ $item->is_done ? 'checked' : '' }} 
                                        class="w-3.5 h-3.5 rounded text-[#28543e] border-current/15"
                                    />
                                    <span class="{{ $item->is_done ? 'line-through text-[#637067] dark:text-[#637067]' : '' }}">{{ $item->text }}</span>
                                </div>
                            @endforeach
                            @if ($note->items->count() > 6)
                                <span class="text-[11px] text-[#637067] italic block pt-1">+{{ $note->items->count() - 6 }} item lainnya</span>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Footer: Labels & Author -->
                <div class="mt-4 pt-3 border-t border-black/5 dark:border-white/5 flex flex-wrap items-center justify-between gap-2 text-[11px] text-[#637067]">
                    <div class="flex flex-wrap gap-1">
                        @if ($note->labels)
                            @foreach ($note->labels as $label)
                                <span class="px-1.5 py-0.5 rounded bg-black/5 dark:bg-[#fffefa]/10 text-[#637067] dark:text-[#b2beb2]">
                                    #{{ $label }}
                                </span>
                            @endforeach
                        @endif
                    </div>
                    <span>{{ $note->creator?->name }}</span>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-[#fffefa] dark:bg-[#1f2c25] rounded-2xl p-12 text-center border border-current/10">
                <p class="text-[#637067] dark:text-[#637067]">Tidak ada catatan pada tampilan ini.</p>
            </div>
        @endforelse
    </div>
</div>
