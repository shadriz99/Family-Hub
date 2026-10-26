<x-auth-layout :title="__('auth.confirm_title')" :intro="__('auth.confirm_intro')">
    <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-5">
        @csrf
        <label for="password" class="block text-sm font-medium">{{ __('auth.password') }}</label>
        <input id="password" name="password" type="password" required autofocus autocomplete="current-password"
            @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
            class="min-h-11 w-full rounded-xl border border-current/25 bg-transparent px-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
        @error('password')
            <p id="password-error" role="alert" class="text-sm text-[#ad3f45] dark:text-[#ffafb1]">{{ $message }}</p>
        @enderror
        <button type="submit" class="min-h-11 w-full rounded-2xl bg-[#28543e] px-4 py-3 font-semibold text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-current dark:bg-[#bad6ae] dark:text-[#151f1b]">
            {{ __('auth.confirm_submit') }}
        </button>
    </form>
</x-auth-layout>
