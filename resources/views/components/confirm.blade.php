@props([
    'id' => 'confirm-dialog',
    'title' => 'Konfirmasi Tindakan',
    'message' => 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
    'confirmText' => 'Lanjutkan',
    'cancelText' => 'Batal',
    'critical' => false,
    'criticalKeyword' => 'HAPUS',
])

<dialog id="{{ $id }}"
    aria-labelledby="{{ $id }}-title"
    x-data="{ confirmation: '' }"
    class="m-auto max-h-[90dvh] w-[min(480px,calc(100%-32px))] rounded-3xl border border-current/15 bg-[#fffefa] p-6 text-current shadow-2xl backdrop:bg-black/40 backdrop:backdrop-blur-xs dark:bg-[#1f2c25]">
    <div class="flex items-center justify-between gap-3">
        <h2 id="{{ $id }}-title" class="font-serif text-xl font-bold">{{ $title }}</h2>
        <button type="button"
            onclick="document.getElementById('{{ $id }}').close()"
            class="flex size-9 items-center justify-center rounded-xl transition-colors hover:bg-current/10"
            aria-label="Tutup dialog">
            <x-heroicon-o-x-mark class="size-5" />
        </button>
    </div>

    <p class="mt-3 text-sm leading-relaxed text-[#637067] dark:text-[#b2beb2]">
        {{ $message }}
    </p>

    @if ($critical)
        <div class="mt-4">
            <label for="{{ $id }}-confirm-input" class="mb-1.5 block text-xs font-semibold text-[#ad3f45] dark:text-[#ffafb1]">
                Ketik <strong class="underline">{{ $criticalKeyword }}</strong> untuk konfirmasi:
            </label>
            <input id="{{ $id }}-confirm-input"
                type="text"
                x-model="confirmation"
                placeholder="{{ $criticalKeyword }}"
                autocomplete="off"
                class="min-h-11 w-full rounded-xl border border-current/25 bg-transparent px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
        </div>
    @endif

    <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <button type="button"
            onclick="document.getElementById('{{ $id }}').close()"
            class="min-h-11 rounded-2xl border border-current/20 px-4 py-2.5 text-sm font-semibold transition-colors hover:bg-current/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
            {{ $cancelText }}
        </button>

        {{ $slot }}
    </div>
</dialog>
