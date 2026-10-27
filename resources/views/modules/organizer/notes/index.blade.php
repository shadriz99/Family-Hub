<div class="space-y-6 max-w-6xl mx-auto pb-16">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Catatan & Checklist</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Ide bersama, checklist persiapan, dan info penting keluarga.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/organizer/notes/create" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm transition">
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
                class="w-full px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-white"
            />
        </div>

        <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 w-full sm:w-auto overflow-x-auto">
            <button wire:click="$set('view', 'active')" class="px-3.5 py-1.5 rounded-lg {{ $view === 'active' ? 'bg-white dark:bg-slate-700 shadow-sm text-slate-900 dark:text-white' : '' }}">
                Catatan Aktif
            </button>
            <button wire:click="$set('view', 'archived')" class="px-3.5 py-1.5 rounded-lg {{ $view === 'archived' ? 'bg-white dark:bg-slate-700 shadow-sm text-slate-900 dark:text-white' : '' }}">
                Arsip
            </button>
            <button wire:click="$set('view', 'trash')" class="px-3.5 py-1.5 rounded-lg {{ $view === 'trash' ? 'bg-white dark:bg-slate-700 shadow-sm text-rose-600 dark:text-rose-400' : '' }}">
                Sampah
            </button>
        </div>
    </div>

    <!-- Label Chips -->
    @if (! empty($allLabels))
        <div class="flex flex-wrap items-center gap-1.5 text-xs">
            <span class="text-slate-400 font-medium mr-1">Label:</span>
            <button wire:click="$set('selectedLabel', '')" class="px-2.5 py-1 rounded-lg border {{ $selectedLabel === '' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400' }}">
                Semua
            </button>
            @foreach ($allLabels as $lbl)
                <button wire:click="$set('selectedLabel', '{{ $lbl }}')" class="px-2.5 py-1 rounded-lg border {{ $selectedLabel === $lbl ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400' }}">
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
                    'green' => 'bg-emerald-50/80 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800/60',
                    'blue' => 'bg-sky-50/80 dark:bg-sky-950/30 border-sky-200 dark:border-sky-800/60',
                    'pink' => 'bg-rose-50/80 dark:bg-rose-950/30 border-rose-200 dark:border-rose-800/60',
                    'purple' => 'bg-purple-50/80 dark:bg-purple-950/30 border-purple-200 dark:border-purple-800/60',
                    default => 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700',
                };
            @endphp
            <div class="rounded-2xl p-5 border shadow-sm flex flex-col justify-between transition hover:shadow-md relative group {{ $colorClasses }}">
                <div>
                    <!-- Top bar: pin & private badges -->
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            @if ($note->is_private)
                                <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">
                                    🔒 Privat
                                </span>
                            @endif
                            @if ($note->is_pinned)
                                <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                    📌 Disematkan
                                </span>
                            @endif
                        </div>

                        <!-- Action controls -->
                        <div class="flex items-center gap-1 text-slate-400 group-hover:opacity-100 opacity-60 transition">
                            @if ($view === 'active')
                                <button type="button" wire:click="togglePin('{{ $note->id }}')" title="{{ $note->is_pinned ? 'Lepas Sematan' : 'Sematkan' }}" class="p-1 hover:text-indigo-600">
                                    📌
                                </button>
                                <button type="button" wire:click="archive('{{ $note->id }}')" title="Arsipkan" class="p-1 hover:text-amber-600">
                                    📦
                                </button>
                                <button type="button" wire:click="delete('{{ $note->id }}')" title="Pindahkan ke sampah" class="p-1 hover:text-rose-600">
                                    🗑️
                                </button>
                            @elseif ($view === 'archived')
                                <button type="button" wire:click="unarchive('{{ $note->id }}')" title="Keluarkan dari arsip" class="p-1 hover:text-emerald-600">
                                    📤
                                </button>
                                <button type="button" wire:click="delete('{{ $note->id }}')" title="Pindahkan ke sampah" class="p-1 hover:text-rose-600">
                                    🗑️
                                </button>
                            @elseif ($view === 'trash')
                                <button type="button" wire:click="restore('{{ $note->id }}')" title="Pulihkan" class="text-xs font-semibold px-2 py-1 bg-emerald-100 text-emerald-800 rounded">
                                    Pulihkan
                                </button>
                                <button type="button" wire:click="forceDeleteNote('{{ $note->id }}')" title="Hapus permanen" class="text-xs font-semibold px-2 py-1 bg-rose-100 text-rose-800 rounded">
                                    Hapus
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Title -->
                    <a href="/organizer/notes/{{ $note->id }}" class="block">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400">
                            {{ $note->title ?: 'Tanpa Judul' }}
                        </h3>
                    </a>

                    <!-- Body / Items -->
                    @if ($note->type->value === 'text' && $note->body)
                        <div class="mt-2 text-sm text-slate-600 dark:text-slate-300 line-clamp-6 prose dark:prose-invert max-w-none text-xs">
                            {!! Str::markdown($note->body, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
                        </div>
                    @elseif ($note->type->value === 'checklist')
                        <div class="mt-2.5 space-y-1.5">
                            @foreach ($note->items->take(6) as $item)
                                <div class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300">
                                    <input 
                                        type="checkbox" 
                                        wire:click="toggleItem('{{ $item->id }}')" 
                                        {{ $item->is_done ? 'checked' : '' }} 
                                        class="w-3.5 h-3.5 rounded text-indigo-600 border-slate-300 dark:border-slate-600"
                                    />
                                    <span class="{{ $item->is_done ? 'line-through text-slate-400 dark:text-slate-500' : '' }}">{{ $item->text }}</span>
                                </div>
                            @endforeach
                            @if ($note->items->count() > 6)
                                <span class="text-[11px] text-slate-400 italic block pt-1">+{{ $note->items->count() - 6 }} item lainnya</span>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Footer: Labels & Author -->
                <div class="mt-4 pt-3 border-t border-black/5 dark:border-white/5 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-400">
                    <div class="flex flex-wrap gap-1">
                        @if ($note->labels)
                            @foreach ($note->labels as $label)
                                <span class="px-1.5 py-0.5 rounded bg-black/5 dark:bg-white/10 text-slate-600 dark:text-slate-300">
                                    #{{ $label }}
                                </span>
                            @endforeach
                        @endif
                    </div>
                    <span>{{ $note->creator?->name }}</span>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-slate-800 rounded-2xl p-12 text-center border border-slate-200 dark:border-slate-700">
                <p class="text-slate-500 dark:text-slate-400">Tidak ada catatan pada tampilan ini.</p>
            </div>
        @endforelse
    </div>
</div>
