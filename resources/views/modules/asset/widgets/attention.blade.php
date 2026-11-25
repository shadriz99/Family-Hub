<x-card>
    <div class="flex items-center justify-between border-b border-current/10 pb-4">
        <div class="flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-xl bg-[#87571f]/10 text-[#87571f] dark:bg-[#edc48b]/15 dark:text-[#edc48b]">
                <x-heroicon-o-wrench class="size-6" />
            </div>
            <div>
                <h3 class="text-sm font-bold tracking-tight text-[#263c32] dark:text-[#f3f1e8]">{{ __('Perhatian Aset') }}</h3>
                <p class="text-xs text-[#637067] dark:text-[#b2beb2]">{{ __('Perawatan, dokumen, & kerusakan') }}</p>
            </div>
        </div>
        <a href="{{ route('assets.maintenance') }}" class="text-xs font-semibold text-[#28543e] hover:underline dark:text-[#bad6ae]">
            {{ __('Lihat Jadwal') }} &rarr;
        </a>
    </div>

    <div class="mt-4 grid grid-cols-3 gap-3 text-center">
        <a href="{{ route('assets.maintenance') }}" class="flex flex-col items-center justify-center rounded-2xl border border-current/10 bg-current/5 p-3 transition-colors hover:bg-current/10">
            <span class="text-lg font-bold tabular-nums {{ $overdueOrSoonCount > 0 ? 'text-[#ad3f45] dark:text-[#ffafb1]' : 'text-[#263c32] dark:text-[#f3f1e8]' }}">
                {{ $overdueOrSoonCount }}
            </span>
            <span class="mt-0.5 text-[11px] text-[#637067] dark:text-[#b2beb2]">{{ __('Perawatan') }}</span>
        </a>

        <a href="{{ route('assets.documents') }}" class="flex flex-col items-center justify-center rounded-2xl border border-current/10 bg-current/5 p-3 transition-colors hover:bg-current/10">
            <span class="text-lg font-bold tabular-nums {{ $expiringDocsCount > 0 ? 'text-[#87571f] dark:text-[#edc48b]' : 'text-[#263c32] dark:text-[#f3f1e8]' }}">
                {{ $expiringDocsCount }}
            </span>
            <span class="mt-0.5 text-[11px] text-[#637067] dark:text-[#b2beb2]">{{ __('Dokumen ≤30h') }}</span>
        </a>

        <a href="{{ route('assets.issues') }}" class="flex flex-col items-center justify-center rounded-2xl border border-current/10 bg-current/5 p-3 transition-colors hover:bg-current/10">
            <span class="text-lg font-bold tabular-nums {{ $openIssuesCount > 0 ? 'text-[#87571f] dark:text-[#edc48b]' : 'text-[#263c32] dark:text-[#f3f1e8]' }}">
                {{ $openIssuesCount }}
            </span>
            <span class="mt-0.5 text-[11px] text-[#637067] dark:text-[#b2beb2]">{{ __('Kerusakan') }}</span>
        </a>
    </div>
</x-card>
