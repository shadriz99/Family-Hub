<x-auth-layout :title="__('Verifikasi Dua Faktor')" :intro="__('Amankan akun Anda dengan verifikasi tambahan.')">
    @if (session('status'))
        <div role="status" class="mb-5 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-3 text-sm text-emerald-800 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <div x-data="{ recovery: false }">
        <div x-show="!recovery">
            <p class="mb-4 text-sm text-[#637067] dark:text-[#b2beb2]">
                {{ __('Masukkan kode verifikasi 6 digit yang baru saja dikirimkan ke alamat email Anda.') }}
            </p>

            <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="code" class="block text-sm font-medium text-[#131d15] dark:text-[#edf3ee]">
                        {{ __('Kode Verifikasi (OTP)') }}
                    </label>
                    <input id="code" type="text" inputmode="numeric" name="code" autofocus autocomplete="one-time-code" maxlength="6"
                        class="mt-1 block w-full rounded-xl border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 px-4 py-2.5 text-center font-mono text-2xl font-semibold tracking-[0.3em] text-[#131d15] dark:text-[#edf3ee] shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                        placeholder="······" />
                    @error('code')
                        <p class="mt-1.5 text-sm text-rose-600 dark:text-rose-400 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full inline-flex justify-center items-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 transition">
                    {{ __('Verifikasi & Masuk') }}
                </button>
            </form>

            <div class="mt-5 flex items-center justify-between text-xs text-[#637067] dark:text-[#b2beb2]">
                <form method="POST" action="{{ route('two-factor.resend') }}">
                    @csrf
                    <button type="submit" class="font-medium underline hover:text-[#131d15] dark:hover:text-white transition">
                        {{ __('Kirim ulang kode OTP') }}
                    </button>
                </form>

                <button type="button" @click="recovery = true" class="font-medium underline hover:text-[#131d15] dark:hover:text-white transition">
                    {{ __('Gunakan Kode Pemulihan') }}
                </button>
            </div>
        </div>

        <div x-show="recovery" x-cloak>
            <p class="mb-4 text-sm text-[#637067] dark:text-[#b2beb2]">
                {{ __('Masukkan salah satu dari 8 kode pemulihan darurat yang Anda simpan saat mengaktifkan 2FA.') }}
            </p>

            <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="recovery_code" class="block text-sm font-medium text-[#131d15] dark:text-[#edf3ee]">
                        {{ __('Kode Pemulihan') }}
                    </label>
                    <input id="recovery_code" type="text" name="recovery_code" autocomplete="off"
                        class="mt-1 block w-full rounded-xl border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 px-4 py-2.5 text-center font-mono text-lg font-semibold tracking-wider uppercase text-[#131d15] dark:text-[#edf3ee] shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                        placeholder="XXXXX-XXXXX" />
                    @error('recovery_code')
                        <p class="mt-1.5 text-sm text-rose-600 dark:text-rose-400 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full inline-flex justify-center items-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 transition">
                    {{ __('Gunakan Pemulihan & Masuk') }}
                </button>
            </form>

            <div class="mt-5 text-center text-xs text-[#637067] dark:text-[#b2beb2]">
                <button type="button" @click="recovery = false" class="font-medium underline hover:text-[#131d15] dark:hover:text-white transition">
                    {{ __('Kembali ke Kode Email (OTP)') }}
                </button>
            </div>
        </div>
    </div>
</x-auth-layout>
