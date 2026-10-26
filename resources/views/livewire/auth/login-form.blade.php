<form method="POST" action="{{ route('login.store') }}" class="space-y-5">
    @csrf
    <div>
        <label for="email" class="mb-2 block text-sm font-medium">{{ __('auth.email') }}</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
            @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
            class="min-h-11 w-full rounded-xl border border-current/25 bg-transparent px-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
        @error('email')
            <p id="email-error" role="alert" class="mt-2 text-sm text-[#ad3f45] dark:text-[#ffafb1]">{{ $message }}</p>
        @enderror
    </div>
    <div x-data="{ visible: false }">
        <label for="password" class="mb-2 block text-sm font-medium">{{ __('auth.password') }}</label>
        <input id="password" name="password" type="password" x-bind:type="visible ? 'text' : 'password'" required autocomplete="current-password"
            @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
            class="min-h-11 w-full rounded-xl border border-current/25 bg-transparent px-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
        @error('password')
            <p id="password-error" role="alert" class="mt-2 text-sm text-[#ad3f45] dark:text-[#ffafb1]">{{ $message }}</p>
        @enderror
        <button type="button" x-on:click="visible = !visible" x-bind:aria-pressed="visible" aria-controls="password"
            class="min-h-11 text-sm underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
            <span x-show="!visible">{{ __('auth.show_password') }}</span>
            <span x-show="visible" x-cloak>{{ __('auth.hide_password') }}</span>
        </button>
    </div>
    <label class="flex min-h-11 items-center gap-3 text-sm">
        <input name="remember" type="checkbox" value="1" class="size-5 accent-[#28543e]">
        {{ __('auth.remember') }}
    </label>
    <button type="submit" class="min-h-11 w-full rounded-2xl bg-[#28543e] px-4 py-3 font-semibold text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-current dark:bg-[#bad6ae] dark:text-[#151f1b]">
        {{ __('auth.submit') }}
    </button>
</form>
