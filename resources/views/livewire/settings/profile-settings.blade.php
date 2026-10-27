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
            <div class="mb-4 rounded-2xl border border-[#059669]/30 bg-[#059669]/10 p-3 text-sm font-medium text-[#059669] dark:text-[#10b981]" role="alert">
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
                    class="mt-1 w-full rounded-2xl border border-current/15 bg-white/70 px-3 py-2 text-sm text-current dark:bg-[#151f1b]" />
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
                    class="mt-1 w-full rounded-2xl border border-current/15 bg-white/70 px-3 py-2 text-sm text-current dark:bg-[#151f1b]" />
                @error('email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit"
                    class="inline-flex items-center justify-center rounded-2xl bg-[#059669] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[#047857] dark:bg-[#10b981] dark:hover:bg-[#059669]">
                    {{ __('Simpan Profil') }}
                </button>
            </div>
        </form>
    </x-card>

    {{-- Ubah Kata Sandi --}}
    <x-card :title="__('Ubah Kata Sandi')">
        @if ($passwordMessage)
            <div class="mb-4 rounded-2xl border border-[#059669]/30 bg-[#059669]/10 p-3 text-sm font-medium text-[#059669] dark:text-[#10b981]" role="alert">
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
                    class="mt-1 w-full rounded-2xl border border-current/15 bg-white/70 px-3 py-2 text-sm text-current dark:bg-[#151f1b]" />
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
                        class="mt-1 w-full rounded-2xl border border-current/15 bg-white/70 px-3 py-2 text-sm text-current dark:bg-[#151f1b]" />
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
                        class="mt-1 w-full rounded-2xl border border-current/15 bg-white/70 px-3 py-2 text-sm text-current dark:bg-[#151f1b]" />
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit"
                    class="inline-flex items-center justify-center rounded-2xl bg-[#059669] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[#047857] dark:bg-[#10b981] dark:hover:bg-[#059669]">
                    {{ __('Perbarui Kata Sandi') }}
                </button>
            </div>
        </form>
    </x-card>

    {{-- Status Keamanan --}}
    <x-card :title="__('Keamanan Akun (2FA & OTP)')">
        <div class="space-y-3 text-sm">
            <div class="flex items-center justify-between border-b border-current/10 pb-3">
                <div>
                    <div class="font-semibold text-current">{{ __('Autentikasi Dua Faktor (Email OTP)') }}</div>
                    <div class="text-xs text-[#637067] dark:text-[#b2beb2]">
                        {{ __('Kode sandi sekali pakai dikirim ke email saat masuk.') }}
                    </div>
                </div>
                <span class="inline-flex items-center rounded-full bg-amber-500/10 px-2.5 py-0.5 text-xs font-semibold text-amber-600 dark:text-amber-400">
                    {{ __('Tertunda (Q-M12)') }}
                </span>
            </div>

            <p class="text-xs text-[#637067] dark:text-[#b2beb2] leading-relaxed">
                {{ __('Sesuai spesifikasi SEC-2 dan keputusan Q-M12, fitur Email OTP opsional akan diaktifkan setelah kebijakan masa berlaku dan pemulihan akun disepakati oleh reviewer. Saat ini akun Anda diamankan oleh perlindungan isolasi token tertutup (invite-only) dan enkripsi kata sandi kuat.') }}
            </p>
        </div>
    </x-card>
</div>
