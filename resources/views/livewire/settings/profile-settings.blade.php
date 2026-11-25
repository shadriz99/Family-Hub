<div class="mx-auto max-w-2xl space-y-6">
    <div>
        <h1 class="font-serif text-2xl font-bold tracking-tight sm:text-3xl">
            {{ __('Profil & Keamanan') }}
        </h1>
        <p class="mt-1 text-sm text-[#637067] dark:text-[#b2beb2]">
            {{ __('Kelola informasi identitas akun Anda dan perbarui kata sandi.') }}
        </p>
    </div>

    @include('livewire.settings.partials.navigation')

    {{-- Profil Pengguna --}}
    <x-card :title="__('Informasi Akun')">
        @if ($profileMessage)
            <div class="mb-4 rounded-2xl border border-[#28543e]/30 bg-[#28543e]/10 p-3 text-sm font-medium text-[#28543e] dark:text-[#bad6ae]" role="alert">
                {{ $profileMessage }}
            </div>
        @endif

        <form wire:submit="updateProfile" class="space-y-4">
            <div>
                <label for="name" class="block text-xs font-semibold text-current/80">
                    {{ __('Nama Lengkap') }}
                </label>
                <input type="text"
                    id="name"
                    wire:model="name"
                    required
                    class="mt-1 w-full rounded-2xl border border-current/15 bg-[#fffefa]/80 px-3 py-2 text-sm text-current dark:bg-[#151f1b]" />
                @error('name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold text-current/80">
                    {{ __('Alamat Email') }}
                </label>
                <input type="email"
                    id="email"
                    wire:model="email"
                    required
                    class="mt-1 w-full rounded-2xl border border-current/15 bg-[#fffefa]/80 px-3 py-2 text-sm text-current dark:bg-[#151f1b]" />
                @error('email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit"
                    class="inline-flex items-center justify-center rounded-2xl bg-[#28543e] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:opacity-90 dark:bg-[#bad6ae] dark:text-[#151f1b]">
                    {{ __('Simpan Profil') }}
                </button>
            </div>
        </form>
    </x-card>

    {{-- Ubah Kata Sandi --}}
    <x-card :title="__('Ubah Kata Sandi')">
        @if ($passwordMessage)
            <div class="mb-4 rounded-2xl border border-[#28543e]/30 bg-[#28543e]/10 p-3 text-sm font-medium text-[#28543e] dark:text-[#bad6ae]" role="alert">
                {{ $passwordMessage }}
            </div>
        @endif

        <form wire:submit="updatePassword" class="space-y-4">
            <div>
                <label for="current_password" class="block text-xs font-semibold text-current/80">
                    {{ __('Kata Sandi Saat Ini') }}
                </label>
                <input type="password"
                    id="current_password"
                    wire:model="current_password"
                    required
                    class="mt-1 w-full rounded-2xl border border-current/15 bg-[#fffefa]/80 px-3 py-2 text-sm text-current dark:bg-[#151f1b]" />
                @error('current_password') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="password" class="block text-xs font-semibold text-current/80">
                        {{ __('Kata Sandi Baru') }}
                    </label>
                    <input type="password"
                        id="password"
                        wire:model="password"
                        required
                        class="mt-1 w-full rounded-2xl border border-current/15 bg-[#fffefa]/80 px-3 py-2 text-sm text-current dark:bg-[#151f1b]" />
                    @error('password') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-current/80">
                        {{ __('Konfirmasi Kata Sandi Baru') }}
                    </label>
                    <input type="password"
                        id="password_confirmation"
                        wire:model="password_confirmation"
                        required
                        class="mt-1 w-full rounded-2xl border border-current/15 bg-[#fffefa]/80 px-3 py-2 text-sm text-current dark:bg-[#151f1b]" />
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit"
                    class="inline-flex items-center justify-center rounded-2xl bg-[#28543e] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:opacity-90 dark:bg-[#bad6ae] dark:text-[#151f1b]">
                    {{ __('Perbarui Kata Sandi') }}
                </button>
            </div>
        </form>
    </x-card>

    {{-- Status Keamanan (2FA & OTP) --}}
    <x-card :title="__('Keamanan Akun (2FA & OTP)')">
        @if ($twoFactorMessage)
            <div class="mb-4 rounded-2xl border border-[#28543e]/30 bg-[#28543e]/10 p-3 text-sm font-medium text-[#28543e] dark:text-[#bad6ae]" role="alert">
                {{ $twoFactorMessage }}
            </div>
        @endif

        {{-- Recovery Codes Modal / Banner --}}
        @if ($showRecoveryCodesModal && !empty($plainRecoveryCodes))
            <div class="mb-5 rounded-2xl border-2 border-emerald-500/30 bg-emerald-500/5 p-4 dark:bg-emerald-950/20">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h4 class="font-semibold text-emerald-800 dark:text-emerald-300">
                            {{ __('Simpan Kode Pemulihan Anda Sekarang') }}
                        </h4>
                        <p class="mt-1 text-xs text-[#637067] dark:text-[#b2beb2] leading-relaxed">
                            {{ __('Simpan 8 kode pemulihan sekali pakai ini di tempat yang aman. Jika Anda kehilangan akses ke email, salah satu kode ini dapat digunakan untuk masuk ke akun Anda. Kode ini hanya ditampilkan sekali!') }}
                        </p>
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-2 font-mono text-xs font-semibold text-center py-2">
                    @foreach ($plainRecoveryCodes as $code)
                        <div class="rounded-xl border border-current/15 bg-[#fffefa]/90 dark:bg-neutral-800 px-2 py-1.5 shadow-sm select-all">
                            {{ $code }}
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex items-center justify-between gap-3 pt-2">
                    <button type="button"
                        onclick="navigator.clipboard.writeText('{{ implode("\n", $plainRecoveryCodes) }}'); alert('Kode pemulihan berhasil disalin ke clipboard!');"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-400 underline underline-offset-2 hover:opacity-80">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        {{ __('Salin Semua Kode') }}
                    </button>

                    <button type="button"
                        wire:click="closeRecoveryModal"
                        class="min-h-11 rounded-xl bg-[#28543e] px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:opacity-90 transition dark:bg-[#bad6ae] dark:text-[#151f1b]">
                        {{ __('Saya Sudah Menyimpan Kode') }}
                    </button>
                </div>
            </div>
        @endif

        <div class="space-y-4 text-sm">
            <div class="flex items-start justify-between border-b border-current/10 pb-3">
                <div>
                    <div class="font-semibold text-current">{{ __('Autentikasi Dua Faktor (Email OTP)') }}</div>
                    <div class="text-xs text-[#637067] dark:text-[#b2beb2] mt-0.5">
                        {{ __('Setiap kali masuk, kode verifikasi 6 digit dikirimkan ke email terdaftar Anda (berlaku 10 menit).') }}
                    </div>
                </div>
                @if ($twoFactorEnabled)
                    <span class="inline-flex items-center rounded-full bg-[#28543e]/10 px-2.5 py-0.5 text-xs font-semibold text-[#28543e] dark:bg-[#bad6ae]/15 dark:text-[#bad6ae]">
                        {{ __('Aktif') }}
                    </span>
                @else
                    <span class="inline-flex items-center rounded-full bg-current/10 px-2.5 py-0.5 text-xs font-semibold text-[#637067] dark:text-[#b2beb2]">
                        {{ __('Nonaktif') }}
                    </span>
                @endif
            </div>

            @if ($twoFactorEnabled)
                <div class="rounded-2xl border border-current/10 bg-[#fffefa]/40 dark:bg-[#fffefa]/5 p-4 space-y-3">
                    <p class="text-xs text-[#637067] dark:text-[#b2beb2] leading-relaxed">
                        {{ __('Akun Anda terlindungi dengan 2FA Email OTP. Untuk menonaktifkan fitur ini, masukkan kata sandi Anda saat ini.') }}
                    </p>

                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 pt-1">
                        <div class="w-full sm:w-64">
                            <input type="password"
                                wire:model="twoFactorPasswordConfirm"
                                placeholder="{{ __('Kata sandi saat ini') }}"
                                class="w-full rounded-xl border border-current/15 bg-[#fffefa]/80 px-3 py-2 text-xs text-current dark:bg-[#151f1b]" />
                            @error('twoFactorPasswordConfirm') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <button type="button"
                            wire:click="disableTwoFactor"
                            class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-rose-500">
                            {{ __('Nonaktifkan 2FA') }}
                        </button>
                    </div>
                </div>
            @else
                <div class="rounded-2xl border border-current/10 bg-[#fffefa]/40 dark:bg-[#fffefa]/5 p-4 space-y-3">
                    <p class="text-xs text-[#637067] dark:text-[#b2beb2] leading-relaxed">
                        {{ __('Aktifkan 2FA Email OTP untuk perlindungan maksimal. Sistem akan menghasilkan 8 kode pemulihan cadangan jika Anda tidak dapat mengakses email.') }}
                    </p>

                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 pt-1">
                        <div class="w-full sm:w-64">
                            <input type="password"
                                wire:model="twoFactorPasswordConfirm"
                                placeholder="{{ __('Kata sandi saat ini') }}"
                                class="w-full rounded-xl border border-current/15 bg-[#fffefa]/80 px-3 py-2 text-xs text-current dark:bg-[#151f1b]" />
                            @error('twoFactorPasswordConfirm') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <button type="button"
                            wire:click="enableTwoFactor"
                            class="inline-flex items-center justify-center rounded-xl bg-[#28543e] px-4 py-2 text-xs font-semibold text-white shadow-sm transition-colors hover:opacity-90 dark:bg-[#bad6ae] dark:text-[#151f1b]">
                            {{ __('Aktifkan 2FA Email') }}
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </x-card>

    {{-- Integrasi Chatbot Telegram --}}
    <x-card :title="__('Integrasi Chatbot Telegram (ODROID)')">
        @if ($telegramMessage)
            <div class="mb-4 rounded-2xl border border-[#28543e]/30 bg-[#28543e]/10 p-3 text-sm font-medium text-[#28543e] dark:text-[#bad6ae]" role="alert">
                {{ $telegramMessage }}
            </div>
        @endif

        <div class="space-y-4 text-sm">
            <div class="flex items-start justify-between border-b border-current/10 pb-4">
                <div>
                    <div class="font-semibold text-current flex items-center gap-2">
                        <span>{{ __('Catat Pengeluaran via Telegram') }}</span>
                        @if ($isTelegramLinked)
                            <span class="inline-flex items-center rounded-full bg-[#28543e]/10 px-2.5 py-0.5 text-xs font-semibold text-[#28543e] dark:bg-[#bad6ae]/15 dark:text-[#bad6ae]">
                                {{ __('Terhubung') }}
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-current/10 px-2.5 py-0.5 text-xs font-semibold text-[#637067] dark:text-[#b2beb2]">
                                {{ __('Belum Terhubung') }}
                            </span>
                        @endif
                    </div>
                    <div class="mt-1 text-xs text-[#637067] dark:text-[#b2beb2] leading-relaxed">
                        {{ __('Catat pengeluaran instan lewat chat ("kopi 25rb", "bensin 50k bca") atau kirim foto struk belanja untuk dipindai OCR lokal langsung di ODROID.') }}
                    </div>
                </div>
            </div>

            @if ($isTelegramLinked)
                <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="text-xs font-medium text-[#637067] dark:text-[#b2beb2]">{{ __('Akun Telegram Terhubung') }}</div>
                        <div class="text-base font-semibold text-current mt-0.5">
                            {{ $telegramUsername ? '@' . $telegramUsername : __('Pengguna Telegram') }}
                        </div>
                    </div>
                    <button type="button"
                        wire:click="disconnectTelegram"
                        wire:confirm="Yakin ingin memutuskan sambungan akun Telegram ini?"
                        class="inline-flex items-center justify-center rounded-2xl border border-red-500/30 px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-500/10 dark:text-red-400">
                        {{ __('Putuskan Sambungan') }}
                    </button>
                </div>
            @else
                <div class="space-y-4">
                    @if ($telegramLinkCode)
                        <div class="rounded-2xl border border-[#28543e]/30 bg-[#28543e]/10 p-4 space-y-3">
                            <div class="text-xs font-medium text-[#637067] dark:text-[#b2beb2]">
                                {{ __('Kode Verifikasi Anda (berlaku 15 menit):') }}
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="font-mono text-2xl font-bold tracking-widest text-[#28543e] dark:text-[#bad6ae] bg-[#fffefa]/80 dark:bg-[#151f1b] px-4 py-1.5 rounded-xl border border-current/15">
                                    {{ $telegramLinkCode }}
                                </div>
                                <a href="https://t.me/{{ $botUsername }}?start={{ $telegramLinkCode }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-[#28543e] px-4 py-2 text-xs font-semibold text-white shadow-sm hover:opacity-90 dark:bg-[#bad6ae] dark:text-[#151f1b]">
                                    <span>{{ __('Buka Telegram') }}</span>
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            </div>
                            <p class="text-xs text-[#637067] dark:text-[#b2beb2]">
                                {{ __('Kirim perintah berikut ke bot Telegram Anda:') }} <code class="rounded bg-black/5 dark:bg-[#fffefa]/10 px-1.5 py-0.5 font-mono">/start {{ $telegramLinkCode }}</code>
                            </p>
                        </div>
                    @endif

                    <div class="flex items-center gap-3 pt-1">
                        <button type="button"
                            wire:click="generateTelegramCode"
                            class="inline-flex items-center justify-center rounded-2xl bg-[#28543e] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:opacity-90 dark:bg-[#bad6ae] dark:text-[#151f1b]">
                            {{ $telegramLinkCode ? __('Perbarui Kode Tautan') : __('Hubungkan Akun Telegram') }}
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </x-card>
</div>
