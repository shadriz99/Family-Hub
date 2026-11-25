@props([
    'label' => 'Tambah',
    'id' => 'quick-add-btn',
])

<button type="button"
    id="{{ $id }}"
    aria-label="{{ $label }}"
    x-bind:disabled="$store.network?.isOffline"
    {{ $attributes->merge(['class' => 'group relative flex flex-col items-center justify-center text-center disabled:opacity-40 disabled:cursor-not-allowed']) }}>
    <span class="-mt-4 flex size-13 items-center justify-center rounded-2xl bg-[#28543e] text-white shadow-md transition-all duration-200 group-hover:scale-105 group-active:scale-95 dark:bg-[#bad6ae] dark:text-[#151f1b]">
        @if ($slot->isEmpty())
            <x-heroicon-o-plus class="size-6 stroke-[2.5] transition-transform duration-200 group-hover:rotate-90" />
        @else
            {{ $slot }}
        @endif
    </span>
    <span class="mt-1 text-xs font-semibold tracking-tight text-current/75">
        {{ $label }}
    </span>
</button>
