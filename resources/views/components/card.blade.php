@props([
    'title' => null,
    'subtitle' => null,
    'badge' => null,
    'as' => 'div',
])

<{{ $as }} {{ $attributes->merge(['class' => 'rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-xs transition-colors dark:bg-[#1f2c25]']) }}>
    @if ($title || $badge || isset($actions))
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                @if ($title)
                    <h2 class="text-base font-semibold tracking-tight">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="text-xs text-[#637067] dark:text-[#b2beb2]">{{ $subtitle }}</p>
                @endif
            </div>
            <div class="flex items-center gap-2">
                @if ($badge)
                    <span class="inline-flex items-center rounded-full bg-current/10 px-2.5 py-0.5 text-xs font-medium">{{ $badge }}</span>
                @endif
                {{ $actions ?? '' }}
            </div>
        </div>
    @endif
    {{ $slot }}
</{{ $as }}>
