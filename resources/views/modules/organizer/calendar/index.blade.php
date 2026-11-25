<div class="space-y-6 max-w-6xl mx-auto pb-16">
    <!-- Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">Kalender Keluarga</h1>
            <p class="text-sm text-[#637067] dark:text-[#637067]">Semua acara, jadwal tugas, tagihan keuangan, dan perawatan rumah tangga dalam satu kalender.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('calendar.export') }}" class="inline-flex min-h-[44px] items-center gap-1.5 px-3.5 py-2 bg-current/5 hover:bg-current/10 dark:bg-[#1f2c25] text-[#263c32] dark:text-[#b2beb2] text-xs font-semibold rounded-xl transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Unduh .ics
            </a>
            <button type="button" wire:click="$set('showEventModal', true)" class="inline-flex min-h-[44px] items-center gap-1.5 px-4 py-2 bg-[#28543e] hover:opacity-90 text-white dark:bg-[#bad6ae] dark:text-[#151f1b] text-xs font-semibold rounded-xl shadow-sm transition">
                + Tambah Acara
            </button>
        </div>
    </div>

    <!-- Controls & Filters Card -->
    <div class="bg-[#fffefa] dark:bg-[#1f2c25] p-4 rounded-2xl shadow-sm border border-current/10 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- Navigation -->
            <div class="flex items-center gap-2">
                <div class="flex items-center bg-current/5 dark:bg-current/10 rounded-xl p-1">
                    <button type="button" wire:click="previousMonth"
                        aria-label="Bulan sebelumnya"
                        class="flex min-h-[44px] min-w-[44px] items-center justify-center text-[#637067] dark:text-[#b2beb2] hover:text-[#263c32] dark:hover:text-white rounded-lg transition-colors">
                        <x-heroicon-s-chevron-left class="size-5" />
                    </button>
                    <span class="px-3 py-1 font-bold text-sm text-[#263c32] dark:text-[#f3f1e8]">
                        {{ $monthName }}
                    </span>
                    <button type="button" wire:click="nextMonth"
                        aria-label="Bulan berikutnya"
                        class="flex min-h-[44px] min-w-[44px] items-center justify-center text-[#637067] dark:text-[#b2beb2] hover:text-[#263c32] dark:hover:text-white rounded-lg transition-colors">
                        <x-heroicon-s-chevron-right class="size-5" />
                    </button>
                </div>
                <button type="button" wire:click="goToToday" class="min-h-[44px] px-3.5 py-2 border border-current/10 text-[#637067] dark:text-[#b2beb2] hover:bg-current/5 text-xs font-semibold rounded-xl">
                    Hari Ini
                </button>
            </div>

            <!-- View Switcher -->
            <div class="flex items-center gap-1 bg-current/5 dark:bg-current/5 p-1 rounded-xl text-xs font-semibold">
                <button type="button" wire:click="$set('view', 'month')" class="px-3 py-1 rounded-lg {{ $view === 'month' ? 'bg-[#fffefa] dark:bg-current/10 shadow-xs text-[#263c32] dark:text-[#f3f1e8]' : 'text-[#637067] dark:text-[#637067]' }}">
                    Bulan
                </button>
                <button type="button" wire:click="$set('view', 'agenda')" class="px-3 py-1 rounded-lg {{ $view === 'agenda' ? 'bg-[#fffefa] dark:bg-current/10 shadow-xs text-[#263c32] dark:text-[#f3f1e8]' : 'text-[#637067] dark:text-[#637067]' }}">
                    Agenda (60 Hari)
                </button>
            </div>
        </div>

        <!-- Filter Chips: Sources & People -->
        <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-current/10 text-xs">
            <!-- Sources -->
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-[#637067] font-medium">Sumber:</span>
                <button type="button" wire:click="toggleSource('events')" class="px-2.5 py-1 rounded-lg border {{ !empty($sourceFilters['events']) ? 'bg-[#28543e]/10 dark:bg-[#bad6ae]/15 border-[#28543e] dark:border-[#bad6ae] text-[#28543e] dark:text-[#bad6ae] font-semibold' : 'border-current/10 text-[#637067] opacity-60' }}">
                    <x-heroicon-o-sparkles class="mr-1 inline-block size-4 align-text-bottom" /> Acara
                </button>
                <button type="button" wire:click="toggleSource('tasks')" class="px-2.5 py-1 rounded-lg border {{ !empty($sourceFilters['tasks']) ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-500 text-[#87571f] dark:text-amber-300 font-semibold' : 'border-current/10 text-[#637067] opacity-60' }}">
                    <x-heroicon-o-check-circle class="mr-1 inline-block size-4 align-text-bottom" /> Tugas
                </button>
                <button type="button" wire:click="toggleSource('finance_planned')" class="px-2.5 py-1 rounded-lg border {{ !empty($sourceFilters['finance_planned']) ? 'bg-[#28543e]/10 dark:bg-[#bad6ae]/15 border-[#28543e] dark:border-[#bad6ae] text-[#28543e] dark:text-[#bad6ae] font-semibold' : 'border-current/10 text-[#637067] opacity-60' }}">
                    <x-heroicon-o-credit-card class="mr-1 inline-block size-4 align-text-bottom" /> Tagihan Finance
                </button>
                <button type="button" wire:click="toggleSource('asset_maintenance')" class="px-2.5 py-1 rounded-lg border {{ !empty($sourceFilters['asset_maintenance']) ? 'bg-sky-50 dark:bg-sky-950/40 border-sky-500 text-sky-700 dark:text-sky-300 font-semibold' : 'border-current/10 text-[#637067] opacity-60' }}">
                    <x-heroicon-o-wrench class="mr-1 inline-block size-4 align-text-bottom" /> Perawatan Aset
                </button>
            </div>

            <!-- Person -->
            <div class="flex items-center gap-1">
                <span class="text-[#637067] font-medium">Orang:</span>
                <button type="button" wire:click="$set('memberFilter', 'all')" class="px-2.5 py-1 rounded-lg {{ $memberFilter === 'all' ? 'bg-current/10 dark:bg-current/10 font-semibold text-[#263c32] dark:text-[#f3f1e8]' : 'text-[#637067]' }}">
                    Semua
                </button>
                <button type="button" wire:click="$set('memberFilter', 'me')" class="px-2.5 py-1 rounded-lg {{ $memberFilter === 'me' ? 'bg-current/10 dark:bg-current/10 font-semibold text-[#263c32] dark:text-[#f3f1e8]' : 'text-[#637067]' }}">
                    Saya
                </button>
                <button type="button" wire:click="$set('memberFilter', 'partner')" class="px-2.5 py-1 rounded-lg {{ $memberFilter === 'partner' ? 'bg-current/10 dark:bg-current/10 font-semibold text-[#263c32] dark:text-[#f3f1e8]' : 'text-[#637067]' }}">
                    Pasangan
                </button>
            </div>
        </div>
    </div>

    @if ($view === 'month')
        <!-- Month Grid View -->
        <div class="bg-[#fffefa] dark:bg-[#1f2c25] rounded-2xl shadow-sm border border-current/10 overflow-hidden">
            <!-- Day of Week Headers (Senin start) -->
            <div class="grid grid-cols-7 text-center py-2.5 border-b border-current/10 text-xs font-bold uppercase text-[#637067] tracking-wider">
                <div>Sen</div>
                <div>Sel</div>
                <div>Rab</div>
                <div>Kam</div>
                <div>Jum</div>
                <div>Sab</div>
                <div class="text-rose-500">Min</div>
            </div>

            <!-- Days Grid -->
            <div class="grid grid-cols-7 auto-rows-fr divide-x divide-y divide-current/10">
                @foreach ($gridDays as $day)
                    <div 
                        wire:click="selectDate('{{ $day['dateString'] }}')"
                        class="min-h-[75px] sm:min-h-[105px] p-1.5 sm:p-2 cursor-pointer transition relative {{ $day['isCurrentMonth'] ? 'bg-[#fffefa] dark:bg-[#1f2c25]' : 'bg-current/5 dark:bg-current/5 text-[#637067]' }} {{ $selectedDate === $day['dateString'] ? 'ring-2 ring-[#28543e] dark:ring-[#bad6ae] z-10' : 'hover:bg-current/5' }}"
                    >
                        <!-- Date Number -->
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold rounded-full w-6 h-6 flex items-center justify-center {{ $day['isToday'] ? 'bg-[#28543e] text-white dark:bg-[#bad6ae] dark:text-[#151f1b] font-bold' : ($day['isCurrentMonth'] ? 'text-[#263c32] dark:text-[#f3f1e8]' : 'text-[#637067]') }}">
                                {{ $day['date']->day }}
                            </span>
                        </div>

                        <!-- Mobile: max 3 colored dots -->
                        <div class="flex sm:hidden items-center justify-center gap-1 mt-2">
                            @foreach ($day['items']->take(3) as $it)
                                <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $it->color }}"></span>
                            @endforeach
                            @if ($day['items']->count() > 3)
                                <span class="text-[9px] text-[#637067]">+</span>
                            @endif
                        </div>

                        <!-- Desktop: Items List (max 3 + more) -->
                        <div class="hidden sm:block space-y-1 mt-1.5">
                            @foreach ($day['items']->take(3) as $it)
                                <div class="px-1.5 py-0.5 rounded text-[11px] font-medium truncate" style="background-color: {{ $it->color }}20; color: {{ $it->color }}">
                                    {{ $it->title }}
                                </div>
                            @endforeach
                            @if ($day['items']->count() > 3)
                                <span class="text-[10px] text-[#637067] font-medium pl-1 block">
                                    +{{ $day['items']->count() - 3 }} lainnya
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Selected Date Details Panel -->
        <div class="bg-[#fffefa] dark:bg-[#1f2c25] p-5 rounded-2xl shadow-sm border border-current/10 space-y-3">
            <h3 class="text-base font-bold text-[#263c32] dark:text-[#f3f1e8] flex items-center gap-2">
                <span><x-heroicon-o-calendar class="mr-1 inline-block size-4 align-text-bottom" /> Agenda Tanggal:</span>
                <span class="text-[#28543e] dark:text-[#bad6ae]">
                    {{ \Carbon\CarbonImmutable::parse($selectedDate)->translatedFormat('l, d F Y') }}
                </span>
            </h3>

            <div class="space-y-2">
                @forelse ($selectedDateItems as $item)
                    <div class="p-3 rounded-xl border border-current/10 flex items-center justify-between gap-3 bg-current/5/50 dark:bg-[#1f2c25]/30">
                        <div class="flex items-center gap-3">
                            <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $item->color }}"></span>
                            <div>
                                <span class="font-semibold text-sm text-[#263c32] dark:text-[#f3f1e8] block">{{ $item->title }}</span>
                                @if ($item->description)
                                    <p class="text-xs text-[#637067] dark:text-[#637067]">{{ $item->description }}</p>
                                @endif
                            </div>
                        </div>
                        <a href="{{ $item->url }}" class="text-xs font-semibold text-[#28543e] dark:text-[#bad6ae] hover:underline flex-shrink-0">
                            Lihat Detail →
                        </a>
                    </div>
                @empty
                    <p class="text-xs text-[#637067] italic py-2">Tidak ada kegiatan yang dijadwalkan pada tanggal ini.</p>
                @endforelse
            </div>
        </div>
    @else
        <!-- Agenda View (60 Days Ahead) -->
        <div class="space-y-4">
            @forelse ($itemsByDate as $dateKey => $itemsOnDate)
                <div class="bg-[#fffefa] dark:bg-[#1f2c25] p-4 rounded-2xl shadow-sm border border-current/10">
                    <h3 class="text-sm font-bold text-[#263c32] dark:text-[#b2beb2] pb-2 mb-2 border-b border-current/10">
                        {{ \Carbon\CarbonImmutable::parse($dateKey)->translatedFormat('l, d F Y') }}
                    </h3>
                    <div class="space-y-2">
                        @foreach ($itemsOnDate as $item)
                            <div class="flex items-center justify-between gap-3 p-2 rounded-xl hover:bg-current/5">
                                <div class="flex items-center gap-3">
                                    <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $item->color }}"></span>
                                    <span class="text-sm font-medium text-[#263c32] dark:text-[#f3f1e8]">{{ $item->title }}</span>
                                </div>
                                <a href="{{ $item->url }}" class="text-xs text-[#28543e] dark:text-[#bad6ae] hover:underline">
                                    Lihat →
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bg-[#fffefa] dark:bg-[#1f2c25] p-12 text-center rounded-2xl border border-current/10">
                    <p class="text-sm text-[#637067] dark:text-[#637067]">Tidak ada agenda untuk 60 hari ke depan.</p>
                </div>
            @endforelse
        </div>
    @endif

    <!-- Add Quick Event Modal -->
    @if ($showEventModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-[#fffefa] dark:bg-[#1f2c25] rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4 border border-current/10">
                <h3 class="text-lg font-bold text-[#263c32] dark:text-[#f3f1e8]">Tambah Acara Baru</h3>

                <form wire:submit.prevent="createQuickEvent" class="space-y-3 text-sm">
                    <div>
                        <label class="block font-medium text-[#263c32] dark:text-[#b2beb2] mb-1">Judul Acara *</label>
                        <input type="text" wire:model="eventTitle" placeholder="Misal: Kontrol Dokter, Arisan Keluarga..." class="w-full px-3 py-2 border rounded-xl dark:bg-[#1f2c25] border-current/10 text-[#263c32] dark:text-[#f3f1e8]" required />
                    </div>

                    <div>
                        <label class="block font-medium text-[#263c32] dark:text-[#b2beb2] mb-1">Jenis Acara</label>
                        <select wire:model="eventKind" class="w-full px-3 py-2 border rounded-xl dark:bg-[#1f2c25] border-current/10 text-[#263c32] dark:text-[#f3f1e8]">
                            <option value="event">Acara Keluarga</option>
                            <option value="birthday">Ulang Tahun (Tahunan)</option>
                            <option value="anniversary">Anniversary (Tahunan)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-medium text-[#263c32] dark:text-[#b2beb2] mb-1">Tanggal *</label>
                        <input type="date" wire:model="eventStartDate" class="w-full px-3 py-2 border rounded-xl dark:bg-[#1f2c25] border-current/10 text-[#263c32] dark:text-[#f3f1e8]" required />
                    </div>

                    <div>
                        <label class="block font-medium text-[#263c32] dark:text-[#b2beb2] mb-1">Lokasi (Opsional)</label>
                        <input type="text" wire:model="eventLocation" placeholder="cth: RS Hermina" class="w-full px-3 py-2 border rounded-xl dark:bg-[#1f2c25] border-current/10 text-[#263c32] dark:text-[#f3f1e8]" />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3">
                        <button type="button" wire:click="$set('showEventModal', false)" class="px-4 py-2 text-sm text-[#637067] dark:text-[#637067] hover:bg-current/5 rounded-xl">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-[#28543e] dark:bg-[#bad6ae] hover:opacity-90 rounded-xl">
                            Simpan Acara
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
