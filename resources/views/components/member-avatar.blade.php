@props([
    'name' => '?',
    'role' => null,
    'color' => null,
    'size' => 'md',
])

@php
    $words = preg_split('/\s+/', trim((string) $name)) ?: [];
    $initials = '';
    if (count($words) >= 2) {
        $initials = mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
    } elseif (count($words) === 1 && $words[0] !== '') {
        $initials = mb_strtoupper(mb_substr($words[0], 0, 2));
    } else {
        $initials = '?';
    }

    $sizeClasses = match ($size) {
        'xs' => 'size-6 text-[10px]',
        'sm' => 'size-8 text-xs',
        'lg' => 'size-12 text-base',
        default => 'size-10 text-sm',
    };

    $defaultColor = $role === 'partner' ? '#7C3AED' : '#059669';
    $effectiveColor = $color ?: $defaultColor;
@endphp

<span {{ $attributes->merge(['class' => "inline-grid shrink-0 place-items-center rounded-full font-bold transition-transform $sizeClasses"]) }}
    style="background-color: {{ $effectiveColor }}18; color: {{ $effectiveColor }}; border: 1.5px solid {{ $effectiveColor }}40;"
    title="{{ $name }}@if ($role) ({{ ucfirst($role) }})@endif"
    aria-label="{{ $name }}">
    {{ $initials }}
</span>
