@props([
    'title' => null,
])

<x-app-layout :title="$title" {{ $attributes }}>
    {{ $slot }}
</x-app-layout>
