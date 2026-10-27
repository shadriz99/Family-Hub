<div class="space-y-5">
    @if ($problem)
        <p role="alert" class="rounded-xl border border-current/20 p-3 text-sm leading-relaxed">{{ $problem }}</p>
        <a href="{{ auth()->check() ? url('/') : route('login') }}"
            class="inline-flex min-h-11 items-center font-medium underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
            {{ auth()->check() ? __('auth.back_home') : __('auth.invite_login') }}
        </a>
    @elseif ($existingAccount)
        <p class="text-sm leading-relaxed text-[#637067] dark:text-[#b2beb2]">{{ __('auth.invite_existing', ['email' => $email]) }}</p>

        @if ($signedInAsInvitee)
            @if ($isNewHousehold)
                <div class="space-y-4">
                    <div>
                        <label for="existing-invite-household-name" class="mb-2 block text-sm font-medium">{{ __('auth.household_name') }}</label>
                        <input id="existing-invite-household-name" type="text" wire:model="householdName" required
                            @error('householdName') aria-invalid="true" aria-describedby="existing-invite-household-name-error" @enderror
                            class="min-h-11 w-full rounded-xl border border-current/25 bg-transparent px-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
                        @error('householdName')
                            <p id="existing-invite-household-name-error" role="alert" class="mt-2 text-sm text-[#ad3f45] dark:text-[#ffafb1]">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="button" wire:click="submit" wire:loading.attr="disabled"
                        class="min-h-11 w-full rounded-2xl bg-[#28543e] px-4 py-3 font-semibold text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-current disabled:opacity-60 dark:bg-[#bad6ae] dark:text-[#151f1b]">
                        {{ __('auth.invite_submit_new_household') }}
                    </button>
                </div>
            @else
                <button type="button" wire:click="submit" wire:loading.attr="disabled"
                    class="min-h-11 w-full rounded-2xl bg-[#28543e] px-4 py-3 font-semibold text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-current disabled:opacity-60 dark:bg-[#bad6ae] dark:text-[#151f1b]">
                    {{ __('auth.invite_join') }}
                </button>
            @endif
        @elseif (auth()->check())
            <p class="text-sm leading-relaxed text-[#637067] dark:text-[#b2beb2]">{{ __('auth.invite_wrong_account', ['email' => auth()->user()->email]) }}</p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="min-h-11 w-full rounded-2xl border border-current/25 px-4 py-3 font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
                    {{ __('auth.invite_logout') }}
                </button>
            </form>
        @else
            <a href="{{ route('login') }}"
                class="inline-flex min-h-11 items-center font-medium underline underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
                {{ __('auth.invite_login_as') }}
            </a>
        @endif
    @else
        @if ($isNewHousehold)
            <p class="text-sm leading-relaxed text-[#637067] dark:text-[#b2beb2]">{{ __('auth.invite_for_new_household', ['email' => $email]) }}</p>
        @else
            <p class="text-sm leading-relaxed text-[#637067] dark:text-[#b2beb2]">{{ __('auth.invite_for', ['household' => $householdName, 'email' => $email]) }}</p>
        @endif

        <form wire:submit="submit" class="space-y-5">
            @if ($isNewHousehold)
                <div>
                    <label for="invite-household-name" class="mb-2 block text-sm font-medium">{{ __('auth.household_name') }}</label>
                    <input id="invite-household-name" type="text" wire:model="householdName" required
                        @error('householdName') aria-invalid="true" aria-describedby="invite-household-name-error" @enderror
                        class="min-h-11 w-full rounded-xl border border-current/25 bg-transparent px-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
                    @error('householdName')
                        <p id="invite-household-name-error" role="alert" class="mt-2 text-sm text-[#ad3f45] dark:text-[#ffafb1]">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <div>
                <label for="invite-name" class="mb-2 block text-sm font-medium">{{ __('auth.name') }}</label>
                <input id="invite-name" type="text" wire:model="name" required autocomplete="name"
                    @error('name') aria-invalid="true" aria-describedby="invite-name-error" @enderror
                    class="min-h-11 w-full rounded-xl border border-current/25 bg-transparent px-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
                @error('name')
                    <p id="invite-name-error" role="alert" class="mt-2 text-sm text-[#ad3f45] dark:text-[#ffafb1]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="invite-password" class="mb-2 block text-sm font-medium">{{ __('auth.password') }}</label>
                <input id="invite-password" type="password" wire:model="password" required autocomplete="new-password"
                    aria-describedby="invite-password-hint{{ $errors->has('password') ? ' invite-password-error' : '' }}"
                    @error('password') aria-invalid="true" @enderror
                    class="min-h-11 w-full rounded-xl border border-current/25 bg-transparent px-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
                <p id="invite-password-hint" class="mt-2 text-xs text-[#637067] dark:text-[#b2beb2]">{{ __('auth.password_hint') }}</p>
                @error('password')
                    <p id="invite-password-error" role="alert" class="mt-2 text-sm text-[#ad3f45] dark:text-[#ffafb1]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="invite-password-confirmation" class="mb-2 block text-sm font-medium">{{ __('auth.password_confirm') }}</label>
                <input id="invite-password-confirmation" type="password" wire:model="password_confirmation" required autocomplete="new-password"
                    class="min-h-11 w-full rounded-xl border border-current/25 bg-transparent px-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
            </div>

            <button type="submit" wire:loading.attr="disabled"
                class="min-h-11 w-full rounded-2xl bg-[#28543e] px-4 py-3 font-semibold text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-current disabled:opacity-60 dark:bg-[#bad6ae] dark:text-[#151f1b]">
                {{ $isNewHousehold ? __('auth.invite_submit_new_household') : __('auth.invite_submit') }}
            </button>
        </form>
    @endif

    @error('invitation')
        <p role="alert" class="rounded-xl border border-[#ad3f45]/40 p-3 text-sm text-[#ad3f45] dark:text-[#ffafb1]">{{ $message }}</p>
    @enderror
</div>
