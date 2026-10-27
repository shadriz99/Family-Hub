<div x-cloak x-show="$store.network?.isOffline"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="-translate-y-full opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="translate-y-0 opacity-100"
    x-transition:leave-end="-translate-y-full opacity-0"
    role="status"
    aria-live="polite"
    class="relative z-50 flex items-center justify-center gap-2 border-b border-[#87571f]/30 bg-[#87571f] px-4 py-2 text-center text-xs font-semibold text-white dark:border-[#edc48b]/40 dark:bg-[#363222] dark:text-[#edc48b]">
    <x-heroicon-s-wifi class="size-4 shrink-0" />
    <span>{{ __('Mode Offline: Anda sedang tidak terhubung ke internet. Data ditampilkan dalam mode baca.') }}</span>
</div>
