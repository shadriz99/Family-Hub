<div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-xs dark:border-stone-800 dark:bg-stone-900">
    <div class="flex items-center justify-between pb-3 border-b border-stone-100 dark:border-stone-800">
        <div class="flex items-center gap-2">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                🛠️
            </span>
            <div>
                <h3 class="text-sm font-semibold text-stone-900 dark:text-white">Perhatian Aset</h3>
                <p class="text-xs text-stone-500 dark:text-stone-400">Perawatan, dokumen, & kerusakan</p>
            </div>
        </div>
        <a href="{{ route('assets.maintenance') }}" class="text-xs font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">
            Lihat Jadwal →
        </a>
    </div>

    <div class="mt-4 grid grid-cols-3 gap-2">
        <a href="{{ route('assets.maintenance') }}" class="flex flex-col items-center justify-center rounded-xl bg-stone-50 p-2.5 text-center transition hover:bg-stone-100 dark:bg-stone-800/60 dark:hover:bg-stone-800">
            <span class="text-lg font-bold {{ $overdueOrSoonCount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-stone-900 dark:text-white' }}">
                {{ $overdueOrSoonCount }}
            </span>
            <span class="mt-0.5 text-[11px] text-stone-600 dark:text-stone-400">Perawatan</span>
        </a>

        <a href="{{ route('assets.documents') }}" class="flex flex-col items-center justify-center rounded-xl bg-stone-50 p-2.5 text-center transition hover:bg-stone-100 dark:bg-stone-800/60 dark:hover:bg-stone-800">
            <span class="text-lg font-bold {{ $expiringDocsCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-stone-900 dark:text-white' }}">
                {{ $expiringDocsCount }}
            </span>
            <span class="mt-0.5 text-[11px] text-stone-600 dark:text-stone-400">Dokumen ≤30h</span>
        </a>

        <a href="{{ route('assets.issues') }}" class="flex flex-col items-center justify-center rounded-xl bg-stone-50 p-2.5 text-center transition hover:bg-stone-100 dark:bg-stone-800/60 dark:hover:bg-stone-800">
            <span class="text-lg font-bold {{ $openIssuesCount > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-stone-900 dark:text-white' }}">
                {{ $openIssuesCount }}
            </span>
            <span class="mt-0.5 text-[11px] text-stone-600 dark:text-stone-400">Kerusakan</span>
        </a>
    </div>
</div>
