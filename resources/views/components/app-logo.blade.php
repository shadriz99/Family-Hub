@props([
    'size' => 'md',
    'withText' => false,
    'subtitle' => null,
])

@php
    $badgeDimensions = match ($size) {
        'xs' => 'size-7 rounded-xl',
        'sm' => 'size-8 rounded-xl',
        'lg' => 'size-12 rounded-2xl',
        'xl' => 'size-16 rounded-3xl',
        default => 'size-10 rounded-2xl',
    };

    $iconSize = match ($size) {
        'xs' => 'size-4',
        'sm' => 'size-4.5',
        'lg' => 'size-6.5',
        'xl' => 'size-9',
        default => 'size-5',
    };

    $titleSize = match ($size) {
        'xs' => 'text-sm',
        'sm' => 'text-base',
        'lg' => 'text-2xl',
        'xl' => 'text-3xl',
        default => 'text-xl',
    };
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-3']) }}>
    <span class="flex {{ $badgeDimensions }} shrink-0 items-center justify-center bg-gradient-to-br from-[#2e5e46] to-[#1c3e2e] text-[#f8f7f2] shadow-sm ring-1 ring-black/5 transition-transform duration-200 hover:scale-105 dark:from-[#bad6ae] dark:to-[#8fad7e] dark:text-[#151f1b] dark:ring-white/10">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="{{ $iconSize }}" aria-hidden="true">
            <path d="M3 14h2v7a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-7h2a.998.998 0 0 0 .913-.593.998.998 0 0 0-.17-1.076l-9-10c-.379-.422-1.107-.422-1.486 0l-9 10A1 1 0 0 0 3 14zm5.653-2.359a2.224 2.224 0 0 1 3.125 0l.224.22.223-.22a2.225 2.225 0 0 1 3.126 0 2.13 2.13 0 0 1 0 3.07L12.002 18l-3.349-3.289a2.13 2.13 0 0 1 0-3.07z"/>
        </svg>
    </span>

    @if ($withText)
        <div class="leading-tight">
            <span class="font-serif {{ $titleSize }} font-bold tracking-tight block text-[#263c32] dark:text-[#f3f1e8]">Family Hub</span>
            @if ($subtitle)
                <span class="block text-xs font-medium text-[#637067] dark:text-[#b2beb2]">{{ $subtitle }}</span>
            @endif
        </div>
    @endif
</div>
