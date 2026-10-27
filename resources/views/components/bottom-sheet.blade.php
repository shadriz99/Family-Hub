@props([
    'id' => 'bottom-sheet',
    'title' => null,
])

<dialog id="{{ $id }}"
    aria-labelledby="{{ $id }}-title"
    {{ $attributes->merge(['class' => 'm-auto max-h-[90dvh] w-[min(520px,calc(100%-24px))] rounded-3xl border border-current/15 bg-[#fffefa] p-6 text-current shadow-2xl backdrop:bg-black/50 backdrop:backdrop-blur-xs dark:bg-[#1f2c25] max-sm:fixed max-sm:inset-x-0 max-sm:bottom-0 max-sm:m-0 max-sm:w-full max-sm:max-w-none max-sm:rounded-b-none max-sm:rounded-t-3xl']) }}>
    @if ($title)
        <div class="mb-4 flex items-center justify-between gap-3 border-b border-current/10 pb-3">
            <h2 id="{{ $id }}-title" class="font-serif text-xl font-bold">{{ $title }}</h2>
            <button type="button"
                onclick="document.getElementById('{{ $id }}').close()"
                class="flex size-9 items-center justify-center rounded-xl transition-colors hover:bg-current/10 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-current"
                aria-label="Tutup">
                <x-heroicon-o-x-mark class="size-5" />
            </button>
        </div>
    @endif

    <div class="max-h-[calc(85dvh-60px)] overflow-y-auto">
        {{ $slot }}
    </div>
</dialog>
