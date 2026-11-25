<x-card>
    <div class="flex items-center justify-between border-b border-current/10 pb-4">
        <div class="flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-xl bg-[#386578]/10 text-[#386578] dark:bg-[#a7cad6]/15 dark:text-[#a7cad6]">
                <x-heroicon-o-calendar-days class="size-6" />
            </div>
            <div>
                <h3 class="text-sm font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">{{ __('Organizer & Agenda') }}</h3>
                <p class="text-xs text-[#637067] dark:text-[#b2beb2]">{{ __('Tugas, belanja, & acara') }}</p>
            </div>
        </div>
        <a href="{{ route('calendar.index') }}" class="text-xs font-semibold text-[#28543e] hover:underline dark:text-[#bad6ae]">
            {{ __('Buka Kalender') }} &rarr;
        </a>
    </div>

    <div class="mt-4 grid grid-cols-3 gap-3 text-center">
        <a href="{{ route('organizer.tasks.index', ['tab' => 'today']) }}" class="group rounded-2xl border border-current/10 bg-current/5 p-3 transition-colors hover:bg-current/10">
            <x-heroicon-o-check-circle class="mx-auto mb-1.5 size-5 text-[#28543e] dark:text-[#bad6ae]" />
            <span class="block text-lg font-bold tabular-nums text-[#263c32] dark:text-[#f3f1e8]">{{ $todayTasks }}</span>
            <span class="mt-0.5 block text-[11px] text-[#637067] dark:text-[#b2beb2]">{{ __('Tugas Hari Ini') }}</span>
        </a>

        <a href="{{ route('organizer.shopping.index') }}" class="group rounded-2xl border border-current/10 bg-current/5 p-3 transition-colors hover:bg-current/10">
            <x-heroicon-o-shopping-bag class="mx-auto mb-1.5 size-5 text-[#386578] dark:text-[#a7cad6]" />
            <span class="block text-lg font-bold tabular-nums text-[#263c32] dark:text-[#f3f1e8]">{{ $activeShoppingLists }}</span>
            <span class="mt-0.5 block text-[11px] text-[#637067] dark:text-[#b2beb2]">{{ __('Daftar Belanja') }}</span>
        </a>

        <a href="{{ route('calendar.index') }}" class="group rounded-2xl border border-current/10 bg-current/5 p-3 transition-colors hover:bg-current/10">
            <x-heroicon-o-calendar class="mx-auto mb-1.5 size-5 text-[#87571f] dark:text-[#edc48b]" />
            <span class="block text-lg font-bold tabular-nums text-[#263c32] dark:text-[#f3f1e8]">{{ $upcomingEvents }}</span>
            <span class="mt-0.5 block text-[11px] text-[#637067] dark:text-[#b2beb2]">{{ __('Acara 7 Hari') }}</span>
        </a>
    </div>
</x-card>
