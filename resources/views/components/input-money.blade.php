@props(['id', 'label'])
<div x-data="moneyInput(@js(['invalid' => __('money.invalid'), 'range' => __('money.range')]))"
    x-modelable="value" {{ $attributes->whereStartsWith('wire:model') }} class="min-w-0">
    <label for="{{ $id }}" class="mb-2 block text-sm font-medium">{{ $label }}</label>
    <input id="{{ $id }}" x-ref="input" type="text" inputmode="text" autocomplete="off"
        x-bind:value="display" x-on:input.stop="update($event)" x-bind:aria-invalid="error ? 'true' : 'false'"
        aria-describedby="{{ $id }}-error"
        {{ $attributes->whereDoesntStartWith('wire:model')->except(['type', 'value', 'id', 'aria-describedby', 'aria-invalid'])->class(['min-h-11 w-full rounded-xl border border-current/25 bg-transparent px-3 tabular-nums focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current']) }}>
    <p id="{{ $id }}-error" role="alert" x-text="error" x-show="error" x-cloak
        class="mt-2 text-sm text-[#ad3f45] dark:text-[#ffafb1]"></p>
</div>
