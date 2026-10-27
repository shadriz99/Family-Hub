@props([
    'title',
    'description' => null,
    'actionUrl' => null,
    'actionLabel' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-3xl border border-dashed border-current/20 p-8 text-center transition-colors sm:p-12']) }}>
    @if (isset($icon))
        <div class="mb-4 inline-flex size-14 items-center justify-center rounded-2xl bg-current/5 text-current/60">
            {{ $icon }}
        </div>
    @else
        <div class="mb-4 inline-flex size-14 items-center justify-center rounded-2xl bg-current/5 text-current/60">
            <x-heroicon-o-inbox class="size-7" />
        </div>
    @endif

    <h3 class="text-base font-semibold tracking-tight">{{ $title }}</h3>

    @if ($description)
        <p class="mt-1.5 max-w-sm text-sm text-[#637067] dark:text-[#b2beb2]">{{ $description }}</p>
    @endif

    @if ($slot->isNotEmpty())
        <div class="mt-4">
            {{ $slot }}
        </div>
    @endif

    @if ($actionUrl && $actionLabel)
        <div class="mt-5">
            <a href="{{ $actionUrl }}"
                class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-[#28543e] px-5 py-2.5 text-sm font-semibold text-white shadow-xs transition-opacity hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current dark:bg-[#bad6ae] dark:text-[#151f1b]">
                {{ $actionLabel }}
            </a>
        </div>
    @endif
</div>
