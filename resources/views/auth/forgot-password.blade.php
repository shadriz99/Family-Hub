<x-auth-layout :title="__('auth.forgot_title')" :intro="__('auth.forgot_intro')">
    @if (session('status'))
        <p role="status" class="mb-5 rounded-xl border border-current/20 p-3 text-sm leading-relaxed">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <div>
            <label for="forgot-email" class="mb-2 block text-sm font-medium">{{ __('auth.email') }}</label>
            <input id="forgot-email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                @error('email') aria-invalid="true" aria-describedby="forgot-email-error" @enderror
                class="min-h-11 w-full rounded-xl border border-current/25 bg-transparent px-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
            @error('email')
                <p id="forgot-email-error" role="alert" class="mt-2 text-sm text-[#ad3f45] dark:text-[#ffafb1]">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
            class="min-h-11 w-full rounded-2xl bg-[#28543e] px-4 py-3 font-semibold text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-current dark:bg-[#bad6ae] dark:text-[#151f1b]">
            {{ __('auth.forgot_submit') }}
        </button>
    </form>

    <p class="mt-6 text-sm">
        <a href="{{ route('login') }}"
            class="inline-flex min-h-11 items-center font-medium underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
            {{ __('auth.back_to_login') }}
        </a>
    </p>
</x-auth-layout>
