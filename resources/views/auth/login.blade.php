<x-auth-layout :title="__('auth.title')" :intro="__('auth.intro')">
    <livewire:auth.login-form />
    <p class="mt-6 text-sm text-[#637067] dark:text-[#b2beb2]">{{ __('auth.invitation') }}</p>
</x-auth-layout>
