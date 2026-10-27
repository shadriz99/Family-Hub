@props([
    'value' => 0,
    'max' => 100,
    'showPercent' => false,
    'label' => null,
])

@php
    $maxVal = (float) ($max > 0 ? $max : 100);
    $currVal = (float) $value;
    $percentage = round(($currVal / $maxVal) * 100);
    $clampedBarWidth = min(100, max(0, $percentage));

    $colorClass = match (true) {
        $percentage >= 100 => 'bg-[#ad3f45] dark:bg-[#ffafb1]',
        $percentage >= 80 => 'bg-[#87571f] dark:bg-[#edc48b]',
        default => 'bg-[#28543e] dark:bg-[#bad6ae]',
    };
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if ($label || $showPercent)
        <div class="mb-1.5 flex items-center justify-between text-xs font-medium text-[#637067] dark:text-[#b2beb2]">
            @if ($label)
                <span>{{ $label }}</span>
            @endif
            @if ($showPercent)
                <span class="tabular-nums font-semibold text-current">{{ $percentage }}%</span>
            @endif
        </div>
    @endif

    <div class="h-2 w-full overflow-hidden rounded-full bg-current/10"
        role="progressbar"
        aria-valuenow="{{ $currVal }}"
        aria-valuemin="0"
        aria-valuemax="{{ $maxVal }}">
        <div class="h-full rounded-full transition-all duration-300 {{ $colorClass }}"
            style="width: {{ $clampedBarWidth }}%;"></div>
    </div>
</div>
