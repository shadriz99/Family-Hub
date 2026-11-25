<div class="space-y-8">
    <div>
        <h1 class="font-serif text-2xl font-bold tracking-tight sm:text-3xl">
            {{ __('Manajemen Sistem & Household') }}
        </h1>
        <p class="mt-1 text-sm text-[#637067] dark:text-[#b2beb2]">
            {{ __('Kelola pendaftaran household baru, status pengguna, dan inventaris keluarga terdaftar tanpa akses data domain privat.') }}
        </p>
    </div>

    @if ($feedbackMessage)
        <div class="rounded-2xl border border-[#28543e]/30 bg-[#28543e]/10 p-3.5 text-sm font-medium text-[#28543e] dark:text-[#bad6ae]" role="alert">
            {{ $feedbackMessage }}
        </div>
    @endif

    @if ($latestInvitationLink)
        <x-copyable-link
            :value="$latestInvitationLink"
            :label="__('Tautan Pendaftaran Household Baru')"
            :helper="__('Bagikan tautan ini kepada calon pemilik keluarga baru. Tautan bersifat sekali pakai dan berlaku selama 7 hari.')" />
    @endif

    @if ($errorMessage)
        <div class="rounded-2xl border border-red-500/30 bg-red-500/10 p-3 text-sm font-medium text-red-600 dark:text-red-400" role="alert">
            {{ $errorMessage }}
        </div>
    @endif

    {{-- Undang Household Baru --}}
    <x-card :title="__('Undang Household Baru')">
        <p class="mb-4 text-xs leading-relaxed text-[#637067] dark:text-[#b2beb2]">
            {{ __('Kirimkan tautan pendaftaran ke alamat email calon pemilik household baru. Pendaftaran aplikasi tertutup (invite-only).') }}
        </p>

        <form wire:submit="inviteNewHousehold" class="space-y-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <input type="email"
                    wire:model="invite_email"
                    required
                    placeholder="email-calon-owner@domain.com"
                    class="flex-1 rounded-2xl border border-current/15 bg-[#fffefa]/80 px-3.5 py-2 text-sm text-current dark:bg-[#151f1b]" />

                <button type="submit"
                    class="inline-flex items-center justify-center rounded-2xl bg-[#28543e] px-5 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:opacity-90 dark:bg-[#bad6ae] dark:text-[#151f1b]">
                    {{ __('Kirim Undangan') }}
                </button>
            </div>
            @error('invite_email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
        </form>

        @if ($pendingInvitations->isNotEmpty())
            <div class="mt-6 pt-4 border-t border-current/10 space-y-3">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-[#637067] dark:text-[#b2beb2]">
                    {{ __('Undangan Household Baru Menunggu Registrasi') }}
                </h3>

                <div class="divide-y divide-current/10">
                    @foreach ($pendingInvitations as $invite)
                        <div class="flex items-center justify-between py-2.5">
                            <div>
                                <div class="font-medium text-sm text-current">{{ $invite->email }}</div>
                                <div class="text-xs text-[#637067] dark:text-[#b2beb2]">
                                    {{ __('Kedaluwarsa:') }} {{ $invite->expires_at->translatedFormat('d M Y H:i') }}
                                </div>
                            </div>

                            <button type="button"
                                wire:click="revokeInvitation('{{ $invite->getKey() }}')"
                                wire:confirm="{{ __('Batalkan undangan untuk email ini?') }}"
                                class="rounded-xl border border-red-500/20 px-3 py-1 text-xs font-semibold text-red-600 dark:text-red-400 transition-colors hover:bg-red-500/10">
                                {{ __('Batalkan') }}
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </x-card>

    {{-- Daftar Household --}}
    <x-card :title="__('Daftar Household Terdaftar')">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-current/10 text-xs font-semibold uppercase tracking-wider text-[#637067] dark:text-[#b2beb2]">
                        <th class="py-2.5 px-3">{{ __('Nama Household') }}</th>
                        <th class="py-2.5 px-3">{{ __('Anggota') }}</th>
                        <th class="py-2.5 px-3">{{ __('Tanggal Dibuat') }}</th>
                        <th class="py-2.5 px-3">{{ __('Ukuran Storage') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-current/10">
                    @forelse ($households as $household)
                        <tr class="hover:bg-current/5 transition-colors">
                            <td class="py-3 px-3 font-semibold text-current">
                                {{ $household->name }}
                            </td>
                            <td class="py-3 px-3 text-xs text-current">
                                <span class="inline-flex items-center rounded-full bg-current/10 px-2 py-0.5 font-medium">
                                    {{ $household->members_count }} / 2
                                </span>
                            </td>
                            <td class="py-3 px-3 text-xs text-[#637067] dark:text-[#b2beb2]">
                                {{ $household->created_at?->translatedFormat('d M Y') }}
                            </td>
                            <td class="py-3 px-3 text-xs text-[#637067] dark:text-[#b2beb2]">
                                {{ __('0 KB') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-xs text-[#637067] dark:text-[#b2beb2]">
                                {{ __('Belum ada household terdaftar.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- Kelola Pengguna --}}
    <x-card :title="__('Kelola Pengguna')">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-current/10 text-xs font-semibold uppercase tracking-wider text-[#637067] dark:text-[#b2beb2]">
                        <th class="py-2.5 px-3">{{ __('Nama & Email') }}</th>
                        <th class="py-2.5 px-3">{{ __('Peran') }}</th>
                        <th class="py-2.5 px-3">{{ __('Status') }}</th>
                        <th class="py-2.5 px-3 text-right">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-current/10">
                    @foreach ($users as $user)
                        <tr class="hover:bg-current/5 transition-colors">
                            <td class="py-3 px-3">
                                <div class="font-semibold text-current">{{ $user->name }}</div>
                                <div class="text-xs text-[#637067] dark:text-[#b2beb2]">{{ $user->email }}</div>
                            </td>
                            <td class="py-3 px-3 text-xs">
                                @if ($user->is_system_admin)
                                    <span class="inline-flex items-center rounded-full bg-amber-500/10 px-2 py-0.5 font-semibold text-amber-600 dark:text-amber-400">
                                        {{ __('Admin Sistem') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-current/10 px-2 py-0.5 font-medium text-current">
                                        {{ __('Pengguna') }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-xs">
                                @if ($user->isDisabled())
                                    <span class="inline-flex items-center rounded-full bg-red-500/10 px-2 py-0.5 font-semibold text-red-600 dark:text-red-400">
                                        {{ __('Dinonaktifkan') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-[#28543e]/10 px-2 py-0.5 font-semibold text-[#28543e] dark:text-[#bad6ae]">
                                        {{ __('Aktif') }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-right">
                                @if (! $user->is_system_admin && auth()->id() !== $user->getKey())
                                    @if ($user->isDisabled())
                                        <button type="button"
                                            wire:click="toggleUserStatus('{{ $user->getKey() }}')"
                                            class="rounded-xl border border-[#28543e]/30 px-3 py-1 text-xs font-semibold text-[#28543e] dark:text-[#bad6ae] transition-colors hover:bg-[#28543e]/10">
                                            {{ __('Aktifkan') }}
                                        </button>
                                    @else
                                        <button type="button"
                                            wire:click="toggleUserStatus('{{ $user->getKey() }}')"
                                            wire:confirm="{{ __('Nonaktifkan akses pengguna ini?') }}"
                                            class="rounded-xl border border-red-500/30 px-3 py-1 text-xs font-semibold text-red-600 dark:text-red-400 transition-colors hover:bg-red-500/10">
                                            {{ __('Nonaktifkan') }}
                                        </button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
</div>
