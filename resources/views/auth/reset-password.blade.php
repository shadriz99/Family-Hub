@php($token = $request->route('token'))

<x-auth-layout :title="__('auth.reset_title')" :intro="__('auth.reset_intro')">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="reset-email" class="mb-2 block text-sm font-medium">{{ __('auth.email') }}</label>
            <input id="reset-email" name="email" type="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username"
                @error('email') aria-invalid="true" aria-describedby="reset-email-error" @enderror
                class="min-h-11 w-full rounded-xl border border-current/25 bg-transparent px-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
            @error('email')
                <p id="reset-email-error" role="alert" class="mt-2 text-sm text-[#ad3f45] dark:text-[#ffafb1]">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="reset-password" class="mb-2 block text-sm font-medium">{{ __('auth.password') }}</label>
            <input id="reset-password" name="password" type="password" required autocomplete="new-password"
                aria-describedby="reset-password-hint{{ $errors->has('password') ? ' reset-password-error' : '' }}"
                @error('password') aria-invalid="true" @enderror
                class="min-h-11 w-full rounded-xl border border-current/25 bg-transparent px-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
            <p id="reset-password-hint" class="mt-2 text-xs text-[#637067] dark:text-[#b2beb2]">{{ __('auth.password_hint') }}</p>
            @error('password')
                <p id="reset-password-error" role="alert" class="mt-2 text-sm text-[#ad3f45] dark:text-[#ffafb1]">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="reset-password-confirmation" class="mb-2 block text-sm font-medium">{{ __('auth.password_confirm') }}</label>
            <input id="reset-password-confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                class="min-h-11 w-full rounded-xl border border-current/25 bg-transparent px-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
        </div>

        <button type="submit"
            class="min-h-11 w-full rounded-2xl bg-[#28543e] px-4 py-3 font-semibold text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-current dark:bg-[#bad6ae] dark:text-[#151f1b]">
            {{ __('auth.reset_submit') }}
        </button>
    </form>

    <p class="mt-6 text-sm">
        <a href="{{ route('login') }}"
            class="inline-flex min-h-11 items-center font-medium underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
            {{ __('auth.back_to_login') }}
        </a>
    </p>
</x-auth-layout>
