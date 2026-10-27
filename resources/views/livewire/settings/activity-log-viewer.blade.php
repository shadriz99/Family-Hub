<div class="mx-auto max-w-2xl space-y-6">
    <div>
        <h1 class="font-serif text-2xl font-bold tracking-tight sm:text-3xl">
            {{ __('Log Aktivitas') }}
        </h1>
        <p class="mt-1 text-sm text-[#637067] dark:text-[#b2beb2]">
            {{ __('Catatan riwayat perubahan penting dan peristiwa pada rumah tangga Anda.') }}
        </p>
    </div>

    @include('livewire.settings.partials.navigation')

    <x-card :title="__('Riwayat Aktivitas Terakhir')">
        @if ($activities->isEmpty())
            <div class="py-8 text-center text-sm text-[#637067] dark:text-[#b2beb2]">
                <x-heroicon-o-clock class="mx-auto size-8 text-current/30 mb-2" />
                <p>{{ __('Belum ada riwayat aktivitas tercatat.') }}</p>
            </div>
        @else
            <div class="divide-y divide-current/10">
                @foreach ($activities as $activity)
                    @php
                        $causerName = $activity->causer ? $activity->causer->name : __('Sistem');
                        $description = match ($activity->description) {
                            'core.household.created' => __('Membuat ruang keluarga bersama'),
                            'core.household.updated' => __('Memperbarui data dan pengaturan keluarga'),
                            'core.household.partner_invited' => __('Mengirim undangan anggota untuk pasangan'),
                            'core.household.partner_removed' => __('Mengeluarkan anggota dari keluarga'),
                            'core.household.member_left' => __('Meninggalkan keluarga bersama'),
                            'core.household.ownership_transferred' => __('Mengalihkan kepemilikan keluarga'),
                            'core.household.invitation_revoked' => __('Membatalkan tautan undangan pasangan'),
                            'core.profile.updated' => __('Memperbarui informasi profil'),
                            'core.profile.password_changed' => __('Mengubah kata sandi akun'),
                            default => $activity->description,
                        };
                    @endphp

                    <div class="flex items-start justify-between py-3">
                        <div class="space-y-0.5">
                            <div class="text-sm font-semibold text-current">
                                {{ $description }}
                            </div>
                            <div class="text-xs text-[#637067] dark:text-[#b2beb2] flex items-center gap-1.5">
                                <span class="font-medium text-current/80">{{ $causerName }}</span>
                                <span>&bull;</span>
                                <span>{{ $activity->created_at?->diffForHumans() }}</span>
                                <span class="hidden sm:inline">({{ $activity->created_at?->translatedFormat('d M Y H:i') }})</span>
                            </div>
                        </div>

                        <span class="inline-flex items-center rounded-lg bg-current/5 px-2 py-0.5 text-[11px] font-medium text-current/70">
                            {{ $activity->log_name ?? 'core' }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>
</div>
