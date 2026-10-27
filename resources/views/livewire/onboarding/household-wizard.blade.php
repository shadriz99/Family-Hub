<div>
    {{-- Stepper Progress Bar --}}
    <nav aria-label="Progress onboarding" class="mb-8">
        <ol class="flex items-center justify-between text-xs font-medium">
            {{-- Step 1 --}}
            <li class="flex flex-1 items-center">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold transition-colors
                        {{ $step === 1 ? 'bg-[#28543e] text-white dark:bg-[#bad6ae] dark:text-[#151f1b]' : ($step > 1 ? 'bg-[#28543e]/20 text-[#28543e] dark:bg-[#bad6ae]/30 dark:text-[#bad6ae]' : 'border border-current/25 text-current/50') }}"
                        @if ($step === 1) aria-current="step" @endif>
                        @if ($step > 1)
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        @else
                            1
                        @endif
                    </span>
                    <span class="hidden sm:inline {{ $step === 1 ? 'font-semibold text-current' : 'text-current/60' }}">
                        {{ __('household.onboarding.step_1_title') }}
                    </span>
                </div>
                <div class="mx-2 h-0.5 flex-1 {{ $step > 1 ? 'bg-[#28543e] dark:bg-[#bad6ae]' : 'bg-current/15' }}"></div>
            </li>

            {{-- Step 2 --}}
            <li class="flex flex-1 items-center">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold transition-colors
                        {{ $step === 2 ? 'bg-[#28543e] text-white dark:bg-[#bad6ae] dark:text-[#151f1b]' : ($step > 2 ? 'bg-[#28543e]/20 text-[#28543e] dark:bg-[#bad6ae]/30 dark:text-[#bad6ae]' : 'border border-current/25 text-current/50') }}"
                        @if ($step === 2) aria-current="step" @endif>
                        @if ($step > 2)
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        @else
                            2
                        @endif
                    </span>
                    <span class="hidden sm:inline {{ $step === 2 ? 'font-semibold text-current' : 'text-current/60' }}">
                        {{ __('household.onboarding.step_2_title') }}
                    </span>
                </div>
                <div class="mx-2 h-0.5 flex-1 {{ $step > 2 ? 'bg-[#28543e] dark:bg-[#bad6ae]' : 'bg-current/15' }}"></div>
            </li>

            {{-- Step 3 --}}
            <li class="flex items-center">
                <div class="flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold transition-colors
                        {{ $step === 3 ? 'bg-[#28543e] text-white dark:bg-[#bad6ae] dark:text-[#151f1b]' : 'border border-current/25 text-current/50' }}"
                        @if ($step === 3) aria-current="step" @endif>
                        3
                    </span>
                    <span class="hidden sm:inline {{ $step === 3 ? 'font-semibold text-current' : 'text-current/60' }}">
                        {{ __('household.onboarding.step_3_title') }}
                    </span>
                </div>
            </li>
        </ol>
    </nav>

    {{-- Step 1: Nama Household --}}
    @if ($step === 1)
        <div class="space-y-6">
            <div>
                <h2 class="text-xl font-semibold">{{ __('household.onboarding.step_1_title') }}</h2>
                <p class="mt-1 text-sm text-[#637067] dark:text-[#b2beb2]">{{ __('household.onboarding.step_1_desc') }}</p>
            </div>

            <div>
                <label for="onboarding-household-name" class="mb-2 block text-sm font-medium">
                    {{ __('household.onboarding.name_label') }}
                </label>
                <input id="onboarding-household-name" type="text" wire:model="householdName"
                    class="min-h-11 w-full rounded-xl border border-current/25 bg-transparent px-3 text-base focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current"
                    placeholder="{{ __('household.onboarding.name_help') }}"
                    @error('householdName') aria-invalid="true" aria-describedby="onboarding-household-name-error" @enderror
                    required autofocus>
                @error('householdName')
                    <p id="onboarding-household-name-error" role="alert" class="mt-2 text-sm text-[#ad3f45] dark:text-[#ffafb1]">
                        {{ $message }}
                    </p>
                @enderror
                <p class="mt-2 text-xs text-[#637067] dark:text-[#b2beb2]">
                    {{ __('household.onboarding.name_help') }}
                </p>
            </div>

            <div class="pt-2">
                <button type="button" wire:click="saveStep1" wire:loading.attr="disabled"
                    class="min-h-11 w-full rounded-2xl bg-[#28543e] px-4 py-3 font-semibold text-white transition-opacity hover:opacity-95 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-current disabled:opacity-60 dark:bg-[#bad6ae] dark:text-[#151f1b]">
                    {{ __('household.onboarding.btn_next') }}
                </button>
            </div>
        </div>
    @endif

    {{-- Step 2: Zona Waktu & Periode Keuangan --}}
    @if ($step === 2)
        <div class="space-y-6">
            <div>
                <h2 class="text-xl font-semibold">{{ __('household.onboarding.step_2_title') }}</h2>
                <p class="mt-1 text-sm text-[#637067] dark:text-[#b2beb2]">{{ __('household.onboarding.step_2_desc') }}</p>
            </div>

            {{-- Timezone --}}
            <div>
                <label for="onboarding-timezone" class="mb-2 block text-sm font-medium">
                    {{ __('household.onboarding.timezone_label') }}
                </label>
                <select id="onboarding-timezone" wire:model="timezone"
                    class="min-h-11 w-full rounded-xl border border-current/25 bg-[#fffefa] px-3 text-base text-current focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current dark:bg-[#1f2c25]"
                    @error('timezone') aria-invalid="true" aria-describedby="onboarding-timezone-error" @enderror>
                    <optgroup label="Indonesia">
                        <option value="Asia/Jakarta">Asia/Jakarta (WIB · UTC+7)</option>
                        <option value="Asia/Makassar">Asia/Makassar (WITA · UTC+8)</option>
                        <option value="Asia/Jayapura">Asia/Jayapura (WIT · UTC+9)</option>
                    </optgroup>
                    <optgroup label="Lainnya">
                        @foreach ($timezones as $tz)
                            @if (! in_array($tz, ['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura'], true))
                                <option value="{{ $tz }}">{{ $tz }}</option>
                            @endif
                        @endforeach
                    </optgroup>
                </select>
                @error('timezone')
                    <p id="onboarding-timezone-error" role="alert" class="mt-2 text-sm text-[#ad3f45] dark:text-[#ffafb1]">
                        {{ $message }}
                    </p>
                @enderror
                <p class="mt-2 text-xs text-[#637067] dark:text-[#b2beb2]">
                    {{ __('household.onboarding.timezone_help') }}
                </p>
            </div>

            {{-- Start Day --}}
            <div>
                <label for="onboarding-start-day" class="mb-2 block text-sm font-medium">
                    {{ __('household.onboarding.start_day_label') }}
                </label>
                <select id="onboarding-start-day" wire:model="startDay"
                    class="min-h-11 w-full rounded-xl border border-current/25 bg-[#fffefa] px-3 text-base text-current focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current dark:bg-[#1f2c25]"
                    @error('startDay') aria-invalid="true" aria-describedby="onboarding-start-day-error" @enderror>
                    @for ($d = 1; $d <= 28; $d++)
                        <option value="{{ $d }}">
                            {{ __('household.onboarding.start_day_option', ['day' => $d]) }}
                            @if ($d === 1) (Awal Bulan) @elseif ($d === 25) (Gajian) @endif
                        </option>
                    @endfor
                </select>
                @error('startDay')
                    <p id="onboarding-start-day-error" role="alert" class="mt-2 text-sm text-[#ad3f45] dark:text-[#ffafb1]">
                        {{ $message }}
                    </p>
                @enderror
                <p class="mt-2 text-xs text-[#637067] dark:text-[#b2beb2]">
                    {{ __('household.onboarding.start_day_help') }}
                </p>
            </div>

            <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row">
                <button type="button" wire:click="previousStep" wire:loading.attr="disabled"
                    class="min-h-11 w-full rounded-2xl border border-current/25 px-4 py-3 font-semibold transition-colors hover:bg-current/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current sm:w-1/3">
                    {{ __('household.onboarding.btn_back') }}
                </button>
                <button type="button" wire:click="saveStep2" wire:loading.attr="disabled"
                    class="min-h-11 w-full rounded-2xl bg-[#28543e] px-4 py-3 font-semibold text-white transition-opacity hover:opacity-95 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-current disabled:opacity-60 dark:bg-[#bad6ae] dark:text-[#151f1b] sm:w-2/3">
                    {{ __('household.onboarding.btn_next') }}
                </button>
            </div>
        </div>
    @endif

    {{-- Step 3: Undang Pasangan (Opsional) --}}
    @if ($step === 3)
        <div class="space-y-6">
            <div>
                <h2 class="text-xl font-semibold">{{ __('household.onboarding.step_3_title') }}</h2>
                <p class="mt-1 text-sm text-[#637067] dark:text-[#b2beb2]">{{ __('household.onboarding.step_3_desc') }}</p>
            </div>

            <div>
                <label for="onboarding-partner-email" class="mb-2 block text-sm font-medium">
                    {{ __('household.onboarding.partner_email_label') }}
                </label>
                <input id="onboarding-partner-email" type="email" wire:model="partnerEmail"
                    class="min-h-11 w-full rounded-xl border border-current/25 bg-transparent px-3 text-base focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current"
                    placeholder="pasangan@keluarga.test"
                    @error('partnerEmail') aria-invalid="true" aria-describedby="onboarding-partner-email-error" @enderror>
                @error('partnerEmail')
                    <p id="onboarding-partner-email-error" role="alert" class="mt-2 text-sm text-[#ad3f45] dark:text-[#ffafb1]">
                        {{ $message }}
                    </p>
                @enderror
                @error('email')
                    <p id="onboarding-partner-email-error" role="alert" class="mt-2 text-sm text-[#ad3f45] dark:text-[#ffafb1]">
                        {{ $message }}
                    </p>
                @enderror
                <p class="mt-2 text-xs text-[#637067] dark:text-[#b2beb2]">
                    {{ __('household.onboarding.partner_email_help') }}
                </p>
            </div>

            <div class="space-y-3 pt-2">
                <button type="button" wire:click="finish" wire:loading.attr="disabled"
                    class="min-h-11 w-full rounded-2xl bg-[#28543e] px-4 py-3 font-semibold text-white transition-opacity hover:opacity-95 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-current disabled:opacity-60 dark:bg-[#bad6ae] dark:text-[#151f1b]">
                    {{ trim($partnerEmail) !== '' ? __('household.onboarding.btn_finish_invite') : __('household.onboarding.btn_finish') }}
                </button>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <button type="button" wire:click="previousStep" wire:loading.attr="disabled"
                        class="min-h-11 w-full rounded-2xl border border-current/25 px-4 py-3 font-semibold transition-colors hover:bg-current/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current sm:w-1/2">
                        {{ __('household.onboarding.btn_back') }}
                    </button>
                    <button type="button" wire:click="skip" wire:loading.attr="disabled"
                        class="min-h-11 w-full rounded-2xl border border-dashed border-current/30 px-4 py-3 font-medium transition-colors hover:bg-current/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current sm:w-1/2">
                        {{ __('household.onboarding.btn_skip') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
