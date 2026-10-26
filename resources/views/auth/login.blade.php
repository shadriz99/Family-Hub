<x-auth-layout :title="__('auth.title')" :intro="__('auth.intro')">
    @if (session('status'))
        <p role="status" class="mb-5 rounded-xl border border-current/20 p-3 text-sm leading-relaxed">{{ session('status') }}</p>
    @endif

    <livewire:auth.login-form />

    <p class="mt-6 text-sm">
        <a href="{{ route('password.request') }}"
            class="inline-flex min-h-11 items-center font-medium underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
            {{ __('auth.forgot_link') }}
        </a>
    </p>
    <p class="mt-2 text-sm text-[#637067] dark:text-[#b2beb2]">{{ __('auth.invitation') }}</p>
</x-auth-layout>
