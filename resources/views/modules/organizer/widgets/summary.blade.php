<div class="bg-white dark:bg-slate-800 p-5 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Organizer & Agenda</h3>
        <a href="/calendar" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
            Buka Kalender →
        </a>
    </div>

    <div class="grid grid-cols-3 gap-3 text-center">
        <a href="/organizer/tasks?tab=today" class="p-3 bg-slate-50 dark:bg-slate-900/40 rounded-xl hover:bg-indigo-50 dark:hover:bg-indigo-950/30 transition group">
            <span class="text-xl block mb-1">📋</span>
            <span class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400">{{ $todayTasks }}</span>
            <span class="text-[11px] text-slate-500 block">Tugas Hari Ini</span>
        </a>

        <a href="/organizer/shopping" class="p-3 bg-slate-50 dark:bg-slate-900/40 rounded-xl hover:bg-sky-50 dark:hover:bg-sky-950/30 transition group">
            <span class="text-xl block mb-1">🛒</span>
            <span class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-sky-600 dark:group-hover:text-sky-400">{{ $activeShoppingLists }}</span>
            <span class="text-[11px] text-slate-500 block">Daftar Belanja</span>
        </a>

        <a href="/calendar" class="p-3 bg-slate-50 dark:bg-slate-900/40 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-950/30 transition group">
            <span class="text-xl block mb-1">🎉</span>
            <span class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400">{{ $upcomingEvents }}</span>
            <span class="text-[11px] text-slate-500 block">Acara 7 Hari</span>
        </a>
    </div>
</div>
