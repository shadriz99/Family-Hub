@props([
    'value',
    'label' => null,
    'helper' => null,
])

<div x-data="{
    copied: false,
    async copy() {
        if (window.copyToClipboard) {
            await window.copyToClipboard('{{ $value }}');
        } else if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText('{{ $value }}');
        } else {
            const el = this.$refs.input;
            if (el) {
                el.focus();
                el.select();
                document.execCommand('copy');
            }
        }
        this.copied = true;
        setTimeout(() => this.copied = false, 2500);
    }
}" {{ $attributes->merge(['class' => 'rounded-2xl border border-[#28543e]/30 bg-[#28543e]/5 p-4 transition-all dark:border-[#bad6ae]/30 dark:bg-[#bad6ae]/10']) }}>
    @if ($label)
        <div class="mb-2 flex items-center justify-between">
            <span class="text-xs font-semibold tracking-wide text-current uppercase">{{ $label }}</span>
            <span x-show="copied" x-cloak class="inline-flex items-center gap-1 text-xs font-semibold text-[#28543e] dark:text-[#bad6ae] animate-pulse">
                <x-heroicon-s-check class="size-3.5" />
                <span>{{ __('Tersalin ke clipboard!') }}</span>
            </span>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
        <div class="relative flex-1">
            <input type="text"
                x-ref="input"
                readonly
                value="{{ $value }}"
                x-on:click="$el.select()"
                class="w-full rounded-xl border border-current/15 bg-white/80 px-3.5 py-2.5 font-mono text-xs text-current shadow-xs transition-colors focus:border-[#28543e] focus:outline-hidden dark:bg-[#151f1b] dark:focus:border-[#bad6ae]" />
        </div>

        <div class="flex items-center gap-2">
            <button type="button"
                x-on:click="copy()"
                class="flex-1 sm:flex-initial inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-[#28543e] px-4 py-2 text-xs font-semibold text-white shadow-xs transition-all hover:opacity-90 active:scale-95 dark:bg-[#bad6ae] dark:text-[#151f1b]">
                <span x-show="!copied" class="inline-flex items-center gap-1.5">
                    <x-heroicon-o-clipboard-document class="size-4" />
                    <span>{{ __('Salin Tautan') }}</span>
                </span>
                <span x-show="copied" x-cloak class="inline-flex items-center gap-1.5 font-bold">
                    <x-heroicon-s-check class="size-4" />
                    <span>{{ __('Tersalin!') }}</span>
                </span>
            </button>

            <a href="{{ $value }}"
                target="_blank"
                rel="noopener"
                title="{{ __('Buka tautan di tab baru') }}"
                class="inline-flex min-h-10 size-10 items-center justify-center rounded-xl border border-current/20 text-current transition-colors hover:bg-current/5">
                <x-heroicon-o-arrow-top-right-on-square class="size-4" />
            </a>
        </div>
    </div>

    @if ($helper)
        <p class="mt-2 text-[11px] text-[#637067] dark:text-[#b2beb2] leading-relaxed">
            {{ $helper }}
        </p>
    @endif
</div>
