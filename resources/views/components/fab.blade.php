@props([
    'label' => 'Tambah',
    'id' => 'quick-add-btn',
])

<button type="button"
    id="{{ $id }}"
    aria-label="{{ $label }}"
    {{ $attributes->merge(['class' => 'group relative flex flex-col items-center justify-center text-center']) }}>
    <span class="-mt-4 flex size-13 items-center justify-center rounded-2xl bg-[#28543e] text-white shadow-md transition-all duration-150 group-active:scale-95 dark:bg-[#bad6ae] dark:text-[#151f1b]">
        {{ $slot->isEmpty() ? '<svg class="size-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>' : $slot }}
    </span>
    <span class="mt-1 text-[10px] font-semibold tracking-tight text-current/75">
        {{ $label }}
    </span>
</button>
