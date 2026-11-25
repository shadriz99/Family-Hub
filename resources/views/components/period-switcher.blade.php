@props([
    'current' => '',
    'prevUrl' => '#',
    'nextUrl' => '#',
    'hasPrev' => true,
    'hasNext' => true,
])

<nav aria-label="Navigasi Periode" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 rounded-2xl border border-current/15 bg-current/5 p-1 text-sm font-semibold']) }}>
    @if ($hasPrev)
        <a href="{{ $prevUrl }}"
            aria-label="Periode sebelumnya"
            class="flex size-11 items-center justify-center rounded-xl transition-colors hover:bg-current/10 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-current">
            <x-heroicon-o-chevron-left class="size-4" />
        </a>
    @else
        <span class="flex size-11 items-center justify-center rounded-xl opacity-30 cursor-not-allowed">
            <x-heroicon-o-chevron-left class="size-4" />
        </span>
    @endif

    <span class="min-w-[120px] px-2 text-center text-xs sm:text-sm font-bold tracking-tight">
        {{ $current }}
    </span>

    @if ($hasNext)
        <a href="{{ $nextUrl }}"
            aria-label="Periode berikutnya"
            class="flex size-11 items-center justify-center rounded-xl transition-colors hover:bg-current/10 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-current">
            <x-heroicon-o-chevron-right class="size-4" />
        </a>
    @else
        <span class="flex size-11 items-center justify-center rounded-xl opacity-30 cursor-not-allowed">
            <x-heroicon-o-chevron-right class="size-4" />
        </span>
    @endif
</nav>
