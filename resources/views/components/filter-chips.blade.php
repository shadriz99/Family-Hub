@props([
    'items' => [],
    'activeId' => null,
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-2 overflow-x-auto pb-1 text-xs font-semibold no-scrollbar']) }}>
    @foreach ($items as $item)
        @php
            $id = $item['id'] ?? $item['value'] ?? $loop->index;
            $label = $item['label'] ?? $item['name'] ?? $id;
            $count = $item['count'] ?? null;
            $isActive = isset($item['active']) ? (bool) $item['active'] : ($activeId !== null && (string) $activeId === (string) $id);
            $url = $item['url'] ?? null;
        @endphp

        @if ($url)
            <a href="{{ $url }}"
                class="inline-flex min-h-9 shrink-0 items-center gap-1.5 rounded-full px-3.5 py-1.5 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current
                    {{ $isActive ? 'bg-[#28543e] text-white dark:bg-[#bad6ae] dark:text-[#151f1b]' : 'border border-current/15 bg-current/5 text-current/80 hover:bg-current/10' }}">
                <span>{{ $label }}</span>
                @if ($count !== null)
                    <span class="rounded-full bg-current/20 px-1.5 py-0.2 text-[10px] font-bold">{{ $count }}</span>
                @endif
            </a>
        @else
            <button type="button"
                @if (isset($item['wireClick'])) wire:click="{{ $item['wireClick'] }}" @endif
                @if (isset($item['click'])) @click="{{ $item['click'] }}" @endif
                class="inline-flex min-h-9 shrink-0 items-center gap-1.5 rounded-full px-3.5 py-1.5 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current
                    {{ $isActive ? 'bg-[#28543e] text-white dark:bg-[#bad6ae] dark:text-[#151f1b]' : 'border border-current/15 bg-current/5 text-current/80 hover:bg-current/10' }}">
                <span>{{ $label }}</span>
                @if ($count !== null)
                    <span class="rounded-full bg-current/20 px-1.5 py-0.2 text-[10px] font-bold">{{ $count }}</span>
                @endif
            </button>
        @endif
    @endforeach
</div>
