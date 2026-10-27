<div class="border-b border-current/10 mb-6">
    <nav class="-mb-px flex space-x-2 sm:space-x-4 overflow-x-auto pb-1" aria-label="Tabs">
        <a href="{{ route('settings.profile') }}"
            class="whitespace-nowrap px-3 py-2 text-sm font-semibold rounded-xl transition-colors {{ request()->routeIs('settings.profile') ? 'bg-[#059669] text-white shadow-sm' : 'text-[#637067] hover:text-current hover:bg-current/5 dark:text-[#b2beb2]' }}">
            {{ __('Profil & Keamanan') }}
        </a>

        <a href="{{ route('settings.household') }}"
            class="whitespace-nowrap px-3 py-2 text-sm font-semibold rounded-xl transition-colors {{ request()->routeIs('settings.household') ? 'bg-[#059669] text-white shadow-sm' : 'text-[#637067] hover:text-current hover:bg-current/5 dark:text-[#b2beb2]' }}">
            {{ __('Keluarga & Anggota') }}
        </a>

        <a href="{{ route('settings.notifications') }}"
            class="whitespace-nowrap px-3 py-2 text-sm font-semibold rounded-xl transition-colors {{ request()->routeIs('settings.notifications') ? 'bg-[#059669] text-white shadow-sm' : 'text-[#637067] hover:text-current hover:bg-current/5 dark:text-[#b2beb2]' }}">
            {{ __('Notifikasi') }}
        </a>

        <a href="{{ route('settings.activity') }}"
            class="whitespace-nowrap px-3 py-2 text-sm font-semibold rounded-xl transition-colors {{ request()->routeIs('settings.activity') ? 'bg-[#059669] text-white shadow-sm' : 'text-[#637067] hover:text-current hover:bg-current/5 dark:text-[#b2beb2]' }}">
            {{ __('Log Aktivitas') }}
        </a>
    </nav>
</div>
