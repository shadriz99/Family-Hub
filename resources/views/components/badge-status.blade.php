@props([
    'status' => null,
    'color' => null,
    'label' => null,
])

@php
    $resolvedStatus = $color ?? $status ?? 'neutral';
    $normalized = strtolower(trim((string) $resolvedStatus));

    $config = match ($normalized) {
        'overdue', 'terlambat', 'danger', 'red' => [
            'class' => 'bg-[#ad3f45]/15 text-[#ad3f45] dark:bg-[#ffafb1]/20 dark:text-[#ffafb1] border border-[#ad3f45]/20 dark:border-[#ffafb1]/30',
            'default_label' => in_array($normalized, ['danger', 'red'], true) ? 'Bahaya' : 'Terlambat',
        ],
        'today', 'hari_ini', 'hari-ini', 'warning', 'amber', 'yellow' => [
            'class' => 'bg-[#87571f]/15 text-[#87571f] dark:bg-[#edc48b]/20 dark:text-[#edc48b] border border-[#87571f]/20 dark:border-[#edc48b]/30',
            'default_label' => in_array($normalized, ['warning', 'amber', 'yellow'], true) ? 'Peringatan' : 'Hari ini',
        ],
        'soon', 'segera', 'info', 'blue', 'sky' => [
            'class' => 'bg-[#386578]/15 text-[#386578] dark:bg-[#a7cad6]/20 dark:text-[#a7cad6] border border-[#386578]/20 dark:border-[#a7cad6]/30',
            'default_label' => in_array($normalized, ['info', 'blue', 'sky'], true) ? 'Info' : 'Segera',
        ],
        'done', 'lunas', 'selesai', 'success', 'emerald', 'green' => [
            'class' => 'bg-[#28543e]/15 text-[#28543e] dark:bg-[#bad6ae]/20 dark:text-[#bad6ae] border border-[#28543e]/20 dark:border-[#bad6ae]/30',
            'default_label' => 'Selesai',
        ],
        default => [
            'class' => 'bg-current/10 text-current/75 border border-current/15',
            'default_label' => $resolvedStatus,
        ],
    };

    $displayLabel = $label ?? $config['default_label'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ' . $config['class']]) }}>
    {{ $slot->isEmpty() ? $displayLabel : $slot }}
</span>
