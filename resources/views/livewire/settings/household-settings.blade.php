<div class="mx-auto max-w-2xl space-y-6">
    <div>
        <h1 class="font-serif text-2xl font-bold tracking-tight sm:text-3xl">
            {{ __('Keluarga & Anggota') }}
        </h1>
        <p class="mt-1 text-sm text-[#637067] dark:text-[#b2beb2]">
            {{ __('Kelola profil rumah tangga, siklus keuangan bulanan, dan keanggotaan pasangan Anda.') }}
        </p>
    </div>

    @include('livewire.settings.partials.navigation')

    @if ($feedbackMessage)
        <div class="rounded-2xl border border-[#059669]/30 bg-[#059669]/10 p-3.5 text-sm font-medium text-[#059669] dark:text-[#10b981]" role="alert">
            {{ $feedbackMessage }}
        </div>
    @endif

    @if ($latestInvitationLink)
        <x-copyable-link
            :value="$latestInvitationLink"
            :label="__('Tautan Undangan Pasangan')"
            :helper="__('Bagikan tautan ini kepada pasangan Anda untuk bergabung ke ruang keluarga bersama. Tautan berlaku selama 7 hari.')" />
    @endif

    @if ($errorMessage)
        <div class="rounded-2xl border border-red-500/30 bg-red-500/10 p-3 text-sm font-medium text-red-600 dark:text-red-400" role="alert">
            {{ $errorMessage }}
        </div>
    @endif

    {{-- Pengaturan Household --}}
    <x-card :title="__('Informasi Rumah Tangga')">
        <form wire:submit="updateHousehold" class="space-y-4">
            <div>
                <label for="household_name" class="block text-xs font-semibold text-current/80">
                    {{ __('Nama Household') }}
                </label>
                <input type="text"
                    id="household_name"
                    wire:model="household_name"
                    @disabled(! $isOwner)
                    required
                    class="mt-1 w-full rounded-2xl border border-current/15 bg-white/70 px-3 py-2 text-sm text-current disabled:opacity-60 disabled:cursor-not-allowed dark:bg-[#151f1b]" />
                @error('household_name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="timezone" class="block text-xs font-semibold text-current/80">
                        {{ __('Zona Waktu') }}
                    </label>
                    <select id="timezone"
                        wire:model="timezone"
                        @disabled(! $isOwner)
                        class="mt-1 w-full rounded-2xl border border-current/15 bg-white/70 px-3 py-2 text-sm text-current disabled:opacity-60 disabled:cursor-not-allowed dark:bg-[#151f1b]">
                        @foreach ($timezones as $tz)
                            <option value="{{ $tz }}">{{ $tz }}</option>
                        @endforeach
                    </select>
                    @error('timezone') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="finance_period_start_day" class="block text-xs font-semibold text-current/80">
                        {{ __('Hari Mulai Siklus Keuangan') }}
                    </label>
                    <select id="finance_period_start_day"
                        wire:model="finance_period_start_day"
                        @disabled(! $isOwner)
                        class="mt-1 w-full rounded-2xl border border-current/15 bg-white/70 px-3 py-2 text-sm text-current disabled:opacity-60 disabled:cursor-not-allowed dark:bg-[#151f1b]">
                        @for ($day = 1; $day <= 28; $day++)
                            <option value="{{ $day }}">{{ __('Tanggal :day', ['day' => $day]) }}</option>
                        @endfor
                    </select>
                    @error('finance_period_start_day') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            @if ($isOwner)
                <div class="flex justify-end pt-2">
                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-2xl bg-[#059669] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[#047857] dark:bg-[#10b981] dark:hover:bg-[#059669]">
                        {{ __('Simpan Perubahan') }}
                    </button>
                </div>
            @endif
        </form>
    </x-card>

    {{-- Daftar Anggota Keluarga --}}
    <x-card :title="__('Daftar Anggota (Maksimal 2 Orang)')">
        <div class="divide-y divide-current/10">
            @foreach ($members as $member)
                <div class="flex items-center justify-between py-3">
                    <div class="flex items-center gap-3">
                        <x-member-avatar :color="$member->color" :name="$member->user->name" />
                        <div>
                            <div class="font-semibold text-current flex items-center gap-2">
                                <span>{{ $member->user->name }}</span>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $member->role->value === 'owner' ? 'bg-[#059669]/10 text-[#059669] dark:text-[#10b981]' : 'bg-blue-500/10 text-blue-600 dark:text-blue-400' }}">
                                    {{ $member->role->value === 'owner' ? __('Pemilik (Owner)') : __('Pasangan (Partner)') }}
                                </span>
                            </div>
                            <div class="text-xs text-[#637067] dark:text-[#b2beb2]">
                                {{ $member->user->email }} &bull; {{ __('Bergabung sejak') }} {{ $member->joined_at?->translatedFormat('d M Y') }}
                            </div>
                        </div>
                    </div>

                    {{-- Action buttons --}}
                    @if ($isOwner && $member->role->value === 'partner')
                        <div class="flex items-center gap-2">
                            <button type="button"
                                wire:click="transferOwnership('{{ $member->user->getKey() }}')"
                                wire:confirm="{{ __('Apakah Anda yakin ingin mengalihkan kepemilikan household ke partner Anda?') }}"
                                class="rounded-xl border border-current/20 px-3 py-1.5 text-xs font-semibold transition-colors hover:bg-current/5">
                                {{ __('Jadikan Pemilik') }}
                            </button>

                            <button type="button"
                                wire:click="removePartner('{{ $member->user->getKey() }}')"
                                wire:confirm="{{ __('Apakah Anda yakin ingin mengeluarkan partner dari household ini?') }}"
                                class="rounded-xl border border-red-500/30 px-3 py-1.5 text-xs font-semibold text-red-600 dark:text-red-400 transition-colors hover:bg-red-500/10">
                                {{ __('Keluarkan') }}
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Partner Self Leave --}}
        @if ($isPartner)
            <div class="mt-4 pt-4 border-t border-current/10 flex justify-end">
                <button type="button"
                    wire:click="leaveHousehold"
                    wire:confirm="{{ __('Apakah Anda yakin ingin meninggalkan household ini? Anda akan keluar dari sesi keluarga bersama.') }}"
                    class="rounded-xl border border-red-500/30 px-4 py-2 text-xs font-semibold text-red-600 dark:text-red-400 transition-colors hover:bg-red-500/10">
                    {{ __('Tinggalkan Keluarga') }}
                </button>
            </div>
        @endif
    </x-card>

    {{-- Undangan Partner (Hanya jika belum ada partner) --}}
    @if ($isOwner && ! $partnerMember)
        <x-card :title="__('Undang Pasangan')">
            <p class="mb-4 text-xs leading-relaxed text-[#637067] dark:text-[#b2beb2]">
                {{ __('Keluarga ini masih memiliki satu slot kosong. Masukkan email pasangan Anda untuk mengirim link undangan resmi.') }}
            </p>

            <form wire:submit="invitePartner" class="space-y-4">
                <div>
                    <label for="partner_email" class="block text-xs font-semibold text-current/80">
                        {{ __('Alamat Email Pasangan') }}
                    </label>
                    <input type="email"
                        id="partner_email"
                        wire:model="partner_email"
                        required
                        placeholder="contoh: pasangan@keluarga.com"
                        class="mt-1 w-full rounded-2xl border border-current/15 bg-white/70 px-3 py-2 text-sm text-current dark:bg-[#151f1b]" />
                    @error('partner_email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-2xl bg-[#059669] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[#047857] dark:bg-[#10b981] dark:hover:bg-[#059669]">
                        {{ __('Kirim Undangan') }}
                    </button>
                </div>
            </form>

            @if ($pendingInvitations->isNotEmpty())
                <div class="mt-6 pt-4 border-t border-current/10 space-y-3">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-[#637067] dark:text-[#b2beb2]">
                        {{ __('Undangan Menunggu Konfirmasi') }}
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
                                    wire:click="cancelInvitation('{{ $invite->getKey() }}')"
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
    @endif

    {{-- Ekspor Data --}}
    <x-card :title="__('Ekspor Data Keluarga (Backup Mandiri)')">
        <p class="mb-4 text-xs leading-relaxed text-[#637067] dark:text-[#b2beb2]">
            {{ __('Unduh salinan seluruh data keluarga Anda dalam arsip ZIP (termasuk profil household, anggota, dan pengaturan keuangan) untuk disimpan sebagai arsip mandiri.') }}
        </p>

        <button type="button"
            wire:click="exportData"
            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-current/20 px-4 py-2.5 text-sm font-semibold transition-colors hover:bg-current/5">
            <x-heroicon-o-arrow-down-tray class="size-4" />
            <span>{{ __('Unduh Data (.ZIP)') }}</span>
        </button>
    </x-card>
</div>
