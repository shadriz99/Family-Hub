@props(['amount', 'colored' => false])
<span {{ $attributes->class(['tabular-nums', 'text-[#ad3f45] dark:text-[#ffafb1]' => $colored && $amount < 0]) }}>{{ \App\Core\Support\Money::format($amount) }}</span>
