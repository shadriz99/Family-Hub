@props([
    'title' => null,
])

@php
    $household = app(\App\Core\Support\HouseholdContext::class)->currentOrNull();
    $user = auth()->user();
    $pageTitle = $title ? "$title · Family Hub" : ($household ? "{$household->name} · Family Hub" : 'Family Hub');
    $modules = config('modules', ['finance' => false, 'organizer' => false, 'asset' => false]);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#28543e">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/svg+xml" href="/icons/icon-192.svg">
    <link rel="apple-touch-icon" href="/icons/icon-192.svg">
    <title>{{ $pageTitle }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script>
        // Inline theme init to avoid flicker
        (function() {
            var stored = localStorage.getItem('theme');
            if (stored === 'dark' || (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
</head>
<body class="min-h-full bg-[#f6f4ee] font-sans text-base text-[#263c32] antialiased transition-colors dark:bg-[#151f1b] dark:text-[#f3f1e8]">
    {{-- Skip Link for Keyboard Accessibility --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:rounded-xl focus:bg-[#28543e] focus:px-4 focus:py-2 focus:text-white focus:shadow-lg dark:focus:bg-[#bad6ae] dark:focus:text-[#151f1b]">
        {{ __('Lewati ke konten utama') }}
    </a>

    {{-- Offline alert banner --}}
    <x-offline-banner />

    <div class="min-h-dvh">
        {{-- Desktop Left Sidebar (>=1024px) --}}
        <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-r border-current/10 bg-[#fffefa] px-6 py-6 transition-colors lg:flex dark:bg-[#1f2c25]"
            aria-label="Navigasi Utama">
            {{-- Brand --}}
            <a href="{{ url('/') }}" class="group transition-opacity hover:opacity-90">
                <x-app-logo with-text :subtitle="config('app.version', 'v0.1')" />
            </a>

            {{-- Household Card --}}
            @if ($household)
                <div class="my-5 rounded-2xl border border-current/10 bg-current/5 p-3.5">
                    <p class="font-semibold text-sm leading-tight truncate">{{ $household->name }}</p>
                    <p class="mt-1 text-xs text-[#637067] dark:text-[#b2beb2]">
                        {{ __('Periode tgl :day', ['day' => $household->finance_period_start_day]) }} · {{ $household->timezone }}
                    </p>
                </div>
            @endif

            {{-- Navigation Menu --}}
            <nav class="flex-1 space-y-1 overflow-y-auto pt-1" aria-label="Menu Aplikasi">
                <a href="{{ url('/') }}"
                    class="flex min-h-11 items-center gap-3 rounded-2xl px-3.5 py-2.5 text-sm font-semibold transition-colors
                        {{ request()->is('/') ? 'bg-[#28543e] text-white dark:bg-[#bad6ae] dark:text-[#151f1b]' : 'text-current/80 hover:bg-current/5' }}">
                    <x-heroicon-o-home class="size-5 shrink-0" />
                    <span>{{ __('Beranda') }}</span>
                </a>

                <a href="{{ $modules['finance'] ? url('/finance') : '#' }}"
                    class="flex min-h-11 items-center justify-between gap-3 rounded-2xl px-3.5 py-2.5 text-sm font-medium transition-colors
                        {{ request()->is('finance*') ? 'bg-[#28543e] text-white dark:bg-[#bad6ae] dark:text-[#151f1b]' : 'text-current/80 hover:bg-current/5' }}
                        {{ ! $modules['finance'] ? 'opacity-70' : '' }}">
                    <span class="flex items-center gap-3">
                        <x-heroicon-o-wallet class="size-5 shrink-0" />
                        <span>{{ __('Keuangan') }}</span>
                    </span>
                    @if (! $modules['finance'])
                        <span class="rounded-full bg-current/10 px-2 py-0.5 text-[10px] font-bold">{{ __('Segera') }}</span>
                    @endif
                </a>

                <a href="{{ $modules['organizer'] ? url('/calendar') : '#' }}"
                    class="flex min-h-11 items-center justify-between gap-3 rounded-2xl px-3.5 py-2.5 text-sm font-medium transition-colors
                        {{ request()->is('calendar*') ? 'bg-[#28543e] text-white dark:bg-[#bad6ae] dark:text-[#151f1b]' : 'text-current/80 hover:bg-current/5' }}
                        {{ ! $modules['organizer'] ? 'opacity-70' : '' }}">
                    <span class="flex items-center gap-3">
                        <x-heroicon-o-calendar-days class="size-5 shrink-0" />
                        <span>{{ __('Kalender') }}</span>
                    </span>
                    @if (! $modules['organizer'])
                        <span class="rounded-full bg-current/10 px-2 py-0.5 text-[10px] font-bold">{{ __('Segera') }}</span>
                    @endif
                </a>

                <a href="{{ $modules['organizer'] ? url('/tasks') : '#' }}"
                    class="flex min-h-11 items-center justify-between gap-3 rounded-2xl px-3.5 py-2.5 text-sm font-medium transition-colors
                        {{ request()->is('tasks*') ? 'bg-[#28543e] text-white dark:bg-[#bad6ae] dark:text-[#151f1b]' : 'text-current/80 hover:bg-current/5' }}
                        {{ ! $modules['organizer'] ? 'opacity-70' : '' }}">
                    <span class="flex items-center gap-3">
                        <x-heroicon-o-check-circle class="size-5 shrink-0" />
                        <span>{{ __('Tugas') }}</span>
                    </span>
                    @if (! $modules['organizer'])
                        <span class="rounded-full bg-current/10 px-2 py-0.5 text-[10px] font-bold">{{ __('Segera') }}</span>
                    @endif
                </a>

                <a href="{{ $modules['organizer'] ? url('/notes') : '#' }}"
                    class="flex min-h-11 items-center justify-between gap-3 rounded-2xl px-3.5 py-2.5 text-sm font-medium transition-colors
                        {{ request()->is('notes*') ? 'bg-[#28543e] text-white dark:bg-[#bad6ae] dark:text-[#151f1b]' : 'text-current/80 hover:bg-current/5' }}
                        {{ ! $modules['organizer'] ? 'opacity-70' : '' }}">
                    <span class="flex items-center gap-3">
                        <x-heroicon-o-document-text class="size-5 shrink-0" />
                        <span>{{ __('Catatan') }}</span>
                    </span>
                    @if (! $modules['organizer'])
                        <span class="rounded-full bg-current/10 px-2 py-0.5 text-[10px] font-bold">{{ __('Segera') }}</span>
                    @endif
                </a>

                <a href="{{ $modules['organizer'] ? url('/shopping') : '#' }}"
                    class="flex min-h-11 items-center justify-between gap-3 rounded-2xl px-3.5 py-2.5 text-sm font-medium transition-colors
                        {{ request()->is('shopping*') ? 'bg-[#28543e] text-white dark:bg-[#bad6ae] dark:text-[#151f1b]' : 'text-current/80 hover:bg-current/5' }}
                        {{ ! $modules['organizer'] ? 'opacity-70' : '' }}">
                    <span class="flex items-center gap-3">
                        <x-heroicon-o-shopping-bag class="size-5 shrink-0" />
                        <span>{{ __('Belanja') }}</span>
                    </span>
                    @if (! $modules['organizer'])
                        <span class="rounded-full bg-current/10 px-2 py-0.5 text-[10px] font-bold">{{ __('Segera') }}</span>
                    @endif
                </a>

                <a href="{{ $modules['asset'] ? url('/assets') : '#' }}"
                    class="flex min-h-11 items-center justify-between gap-3 rounded-2xl px-3.5 py-2.5 text-sm font-medium transition-colors
                        {{ request()->is('assets*') ? 'bg-[#28543e] text-white dark:bg-[#bad6ae] dark:text-[#151f1b]' : 'text-current/80 hover:bg-current/5' }}
                        {{ ! $modules['asset'] ? 'opacity-70' : '' }}">
                    <span class="flex items-center gap-3">
                        <x-heroicon-o-cube class="size-5 shrink-0" />
                        <span>{{ __('Aset') }}</span>
                    </span>
                    @if (! $modules['asset'])
                        <span class="rounded-full bg-current/10 px-2 py-0.5 text-[10px] font-bold">{{ __('Segera') }}</span>
                    @endif
                </a>
            </nav>

            {{-- People in Household --}}
            @if ($household && $household->members->isNotEmpty())
                <div class="mt-4 border-t border-current/10 pt-4">
                    <p class="mb-2 text-xs font-semibold text-[#637067] dark:text-[#b2beb2]">{{ __('Anggota Keluarga') }}</p>
                    <div class="flex items-center gap-2">
                        @foreach ($household->members as $member)
                            <x-member-avatar
                                :name="$member->user?->name ?? 'Anggota'"
                                :role="$member->role->value"
                                :color="$member->color"
                                size="sm" />
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Sidebar Footer (Theme Toggle + User & Logout) --}}
            <div class="mt-4 border-t border-current/10 pt-4 space-y-2">
                <button type="button"
                    onclick="window.toggleDarkMode()"
                    class="flex min-h-11 w-full items-center justify-between rounded-2xl px-3.5 py-2 text-xs font-semibold text-[#637067] transition-colors hover:bg-current/5 dark:text-[#b2beb2]">
                    <span class="flex items-center gap-2">
                        <x-heroicon-o-moon class="size-4 hidden dark:inline" />
                        <x-heroicon-o-sun class="size-4 inline dark:hidden" />
                        <span>{{ __('Mode Tampilan') }}</span>
                    </span>
                    <span class="capitalize text-[11px] opacity-75">{{ __('Ganti') }}</span>
                </button>

                @if ($user)
                    <div class="flex items-center justify-between gap-2 pt-1">
                        <div class="flex items-center gap-2.5 truncate">
                            <x-member-avatar :name="$user->name" size="xs" />
                            <span class="truncate text-xs font-semibold">{{ $user->name }}</span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                aria-label="{{ __('Keluar') }}"
                                title="{{ __('Keluar') }}"
                                class="flex size-9 items-center justify-center rounded-xl transition-colors hover:bg-current/10 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-current">
                                <x-heroicon-o-arrow-right-start-on-rectangle class="size-4 text-[#ad3f45] dark:text-[#ffafb1]" />
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </aside>

        {{-- Main Content Shell (offset by sidebar on desktop) --}}
        <div class="lg:ml-64 flex min-h-dvh flex-col">
            {{-- Top Bar Header --}}
            <header class="sticky top-0 z-20 flex min-h-16 items-center justify-between border-b border-current/10 bg-[#fffefa]/90 px-4 backdrop-blur-md transition-colors sm:px-6 lg:px-8 dark:bg-[#1f2c25]/90">
                <div class="flex items-center gap-3">
                    <a href="{{ url('/') }}" class="flex items-center gap-2.5 lg:hidden group">
                        <x-app-logo size="xs" />
                        <span class="font-serif text-lg font-bold sm:text-xl">
                            {{ $household?->name ?? 'Family Hub' }}
                        </span>
                    </a>
                    @if ($title)
                        <span class="hidden font-serif text-lg font-bold sm:inline">
                            {{ $title }}
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    {{-- Quick Action Button (Desktop Only) --}}
                    <button type="button"
                        onclick="document.getElementById('quick-add-sheet').showModal()"
                        class="hidden lg:inline-flex min-h-10 items-center gap-2 rounded-2xl bg-[#28543e] px-4 py-2 text-xs font-semibold text-white shadow-xs transition-opacity hover:opacity-90 dark:bg-[#bad6ae] dark:text-[#151f1b]">
                        <x-heroicon-s-plus class="size-4" />
                        <span>{{ __('Tambah Cepat') }}</span>
                    </button>

                    {{-- Theme Toggle Mobile --}}
                    <button type="button"
                        onclick="window.toggleDarkMode()"
                        aria-label="{{ __('Ganti tema') }}"
                        class="flex size-10 items-center justify-center rounded-2xl border border-current/15 text-current/80 transition-colors hover:bg-current/5 lg:hidden">
                        <x-heroicon-o-moon class="size-5 hidden dark:inline" />
                        <x-heroicon-o-sun class="size-5 inline dark:hidden" />
                    </button>

                    {{-- Notification Bell --}}
                    <a href="{{ route('notifications.index') }}"
                        aria-label="{{ __('Notifikasi') }}"
                        class="relative flex size-10 items-center justify-center rounded-2xl border border-current/15 text-current/80 transition-colors hover:bg-current/5">
                        <x-heroicon-o-bell class="size-5" />
                        @if ($user && $user->unreadNotifications()->count() > 0)
                            <span class="absolute top-2 right-2 size-2 rounded-full bg-[#059669] ring-2 ring-white dark:bg-[#10b981] dark:ring-[#1f2c25]"></span>
                        @endif
                    </a>

                    {{-- User Profile Avatar / Logout Menu Mobile --}}
                    @if ($user)
                        <div class="lg:hidden">
                            <x-member-avatar :name="$user->name" size="sm" />
                        </div>
                    @endif
                </div>
            </header>

            {{-- Main Page Body --}}
            <main id="main-content" class="mx-auto w-full max-w-6xl flex-1 px-4 py-6 pb-28 sm:px-6 lg:px-8 lg:pb-10">
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Mobile Bottom Navigation (<1024px) --}}
    <nav class="fixed inset-x-0 bottom-0 z-30 flex min-h-[72px] items-center justify-around border-t border-current/10 bg-[#fffefa]/95 px-2 backdrop-blur-md transition-colors lg:hidden dark:bg-[#1f2c25]/95"
        style="padding-bottom: max(6px, env(safe-area-inset-bottom));"
        aria-label="{{ __('Navigasi Mobile') }}">
        {{-- 1. Beranda --}}
        <a href="{{ url('/') }}"
            class="flex min-h-12 flex-1 flex-col items-center justify-center gap-1 text-[11px] font-medium transition-colors
                {{ request()->is('/') ? 'font-bold text-[#28543e] dark:text-[#bad6ae]' : 'text-[#637067] dark:text-[#b2beb2]' }}">
            <i class="bx {{ request()->is('/') ? 'bxs-home-heart' : 'bx-home-heart' }} text-2xl leading-none"></i>
            <span>{{ __('Beranda') }}</span>
        </a>

        {{-- 2. Keuangan --}}
        <a href="{{ $modules['finance'] ? url('/finance') : '#' }}"
            class="flex min-h-12 flex-1 flex-col items-center justify-center gap-1 text-[11px] font-medium transition-colors
                {{ request()->is('finance*') ? 'font-bold text-[#28543e] dark:text-[#bad6ae]' : 'text-[#637067] dark:text-[#b2beb2]' }}
                {{ ! $modules['finance'] ? 'opacity-60' : '' }}">
            <x-heroicon-o-wallet class="size-5" />
            <span>{{ __('Keuangan') }}</span>
        </a>

        {{-- 3. Center FAB [+] --}}
        <div class="flex flex-1 justify-center">
            <x-fab label="{{ __('Tambah') }}" id="mobile-fab-btn" onclick="document.getElementById('quick-add-sheet').showModal()" />
        </div>

        {{-- 4. Kalender --}}
        <a href="{{ $modules['organizer'] ? url('/calendar') : '#' }}"
            class="flex min-h-12 flex-1 flex-col items-center justify-center gap-1 text-[11px] font-medium transition-colors
                {{ request()->is('calendar*') ? 'font-bold text-[#28543e] dark:text-[#bad6ae]' : 'text-[#637067] dark:text-[#b2beb2]' }}
                {{ ! $modules['organizer'] ? 'opacity-60' : '' }}">
            <x-heroicon-o-calendar-days class="size-5" />
            <span>{{ __('Kalender') }}</span>
        </a>

        {{-- 5. Lainnya --}}
        <button type="button"
            onclick="document.getElementById('more-sheet').showModal()"
            class="flex min-h-12 flex-1 flex-col items-center justify-center gap-1 text-[11px] font-medium text-[#637067] transition-colors dark:text-[#b2beb2]">
            <x-heroicon-o-squares-2x2 class="size-5" />
            <span>{{ __('Lainnya') }}</span>
        </button>
    </nav>

    {{-- FAB [+] Quick Add Bottom Sheet / Dialog --}}
    <x-bottom-sheet id="quick-add-sheet" :title="__('Tambah Cepat')">
        <div class="space-y-2 py-1">
            @if ($modules['finance'])
                <a href="{{ url('/finance/transactions/create?type=expense') }}"
                    class="flex min-h-12 items-center gap-3.5 rounded-2xl bg-current/5 px-4 py-3 font-semibold transition-colors hover:bg-current/10">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-[#ad3f45]/15 text-[#ad3f45] dark:bg-[#ffafb1]/20 dark:text-[#ffafb1]">
                        <x-heroicon-o-arrow-up-right class="size-5" />
                    </span>
                    <span>{{ __('Catat Pengeluaran') }}</span>
                </a>
                <a href="{{ url('/finance/transactions/create?type=income') }}"
                    class="flex min-h-12 items-center gap-3.5 rounded-2xl bg-current/5 px-4 py-3 font-semibold transition-colors hover:bg-current/10">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-[#28543e]/15 text-[#28543e] dark:bg-[#bad6ae]/20 dark:text-[#bad6ae]">
                        <x-heroicon-o-arrow-down-left class="size-5" />
                    </span>
                    <span>{{ __('Catat Pemasukan') }}</span>
                </a>
                <a href="{{ url('/finance/transactions/create?type=transfer') }}"
                    class="flex min-h-12 items-center gap-3.5 rounded-2xl bg-current/5 px-4 py-3 font-semibold transition-colors hover:bg-current/10">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-[#386578]/15 text-[#386578] dark:bg-[#a7cad6]/20 dark:text-[#a7cad6]">
                        <x-heroicon-o-arrows-right-left class="size-5" />
                    </span>
                    <span>{{ __('Transfer Saldo') }}</span>
                </a>
            @endif

            @if ($modules['organizer'])
                <a href="{{ url('/tasks/create') }}"
                    class="flex min-h-12 items-center gap-3.5 rounded-2xl bg-current/5 px-4 py-3 font-semibold transition-colors hover:bg-current/10">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-current/10">
                        <x-heroicon-o-check-circle class="size-5" />
                    </span>
                    <span>{{ __('Tambah Tugas') }}</span>
                </a>
                <a href="{{ url('/notes/create') }}"
                    class="flex min-h-12 items-center gap-3.5 rounded-2xl bg-current/5 px-4 py-3 font-semibold transition-colors hover:bg-current/10">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-current/10">
                        <x-heroicon-o-document-text class="size-5" />
                    </span>
                    <span>{{ __('Catatan Baru') }}</span>
                </a>
                <a href="{{ url('/shopping') }}"
                    class="flex min-h-12 items-center gap-3.5 rounded-2xl bg-current/5 px-4 py-3 font-semibold transition-colors hover:bg-current/10">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-current/10">
                        <x-heroicon-o-shopping-bag class="size-5" />
                    </span>
                    <span>{{ __('Daftar Belanja') }}</span>
                </a>
            @endif

            @if ($modules['asset'])
                <a href="{{ url('/assets/create') }}"
                    class="flex min-h-12 items-center gap-3.5 rounded-2xl bg-current/5 px-4 py-3 font-semibold transition-colors hover:bg-current/10">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-[#28543e]/15 text-[#28543e] dark:bg-[#bad6ae]/20 dark:text-[#bad6ae]">
                        <x-heroicon-o-cube class="size-5" />
                    </span>
                    <span>{{ __('Tambah Aset Baru') }}</span>
                </a>
                <a href="{{ url('/assets/issues/create') }}"
                    class="flex min-h-12 items-center gap-3.5 rounded-2xl bg-current/5 px-4 py-3 font-semibold transition-colors hover:bg-current/10">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-[#87571f]/15 text-[#87571f] dark:bg-[#edc48b]/20 dark:text-[#edc48b]">
                        <x-heroicon-o-wrench class="size-5" />
                    </span>
                    <span>{{ __('Laporan Kerusakan') }}</span>
                </a>
            @endif

            @if (! $modules['finance'] && ! $modules['organizer'] && ! $modules['asset'])
                <x-empty-state
                    :title="__('Modul Belum Dirilis')"
                    :description="__('Fitur pencatatan cepat akan aktif otomatis begitu Modul Keuangan (Phase 1) atau Organizer (Phase 2) dirilis.')">
                </x-empty-state>
            @endif
        </div>
    </x-bottom-sheet>

    {{-- "Lainnya" Mobile More Menu Bottom Sheet --}}
    <x-bottom-sheet id="more-sheet" :title="__('Menu Lainnya')">
        <div class="space-y-1.5 py-1">
            <a href="{{ $modules['organizer'] ? url('/tasks') : '#' }}"
                class="flex min-h-12 items-center justify-between rounded-2xl bg-current/5 px-4 py-3 font-medium transition-colors hover:bg-current/10 {{ ! $modules['organizer'] ? 'opacity-60' : '' }}">
                <span class="flex items-center gap-3">
                    <x-heroicon-o-check-circle class="size-5" />
                    <span>{{ __('Tugas') }}</span>
                </span>
                @if (! $modules['organizer']) <span class="rounded-full bg-current/10 px-2 py-0.5 text-[10px] font-bold">{{ __('Segera') }}</span> @endif
            </a>

            <a href="{{ $modules['organizer'] ? url('/notes') : '#' }}"
                class="flex min-h-12 items-center justify-between rounded-2xl bg-current/5 px-4 py-3 font-medium transition-colors hover:bg-current/10 {{ ! $modules['organizer'] ? 'opacity-60' : '' }}">
                <span class="flex items-center gap-3">
                    <x-heroicon-o-document-text class="size-5" />
                    <span>{{ __('Catatan') }}</span>
                </span>
                @if (! $modules['organizer']) <span class="rounded-full bg-current/10 px-2 py-0.5 text-[10px] font-bold">{{ __('Segera') }}</span> @endif
            </a>

            <a href="{{ $modules['organizer'] ? url('/shopping') : '#' }}"
                class="flex min-h-12 items-center justify-between rounded-2xl bg-current/5 px-4 py-3 font-medium transition-colors hover:bg-current/10 {{ ! $modules['organizer'] ? 'opacity-60' : '' }}">
                <span class="flex items-center gap-3">
                    <x-heroicon-o-shopping-bag class="size-5" />
                    <span>{{ __('Belanja') }}</span>
                </span>
                @if (! $modules['organizer']) <span class="rounded-full bg-current/10 px-2 py-0.5 text-[10px] font-bold">{{ __('Segera') }}</span> @endif
            </a>

            <a href="{{ $modules['asset'] ? url('/assets') : '#' }}"
                class="flex min-h-12 items-center justify-between rounded-2xl bg-current/5 px-4 py-3 font-medium transition-colors hover:bg-current/10 {{ ! $modules['asset'] ? 'opacity-60' : '' }}">
                <span class="flex items-center gap-3">
                    <x-heroicon-o-cube class="size-5" />
                    <span>{{ __('Aset') }}</span>
                </span>
                @if (! $modules['asset']) <span class="rounded-full bg-current/10 px-2 py-0.5 text-[10px] font-bold">{{ __('Segera') }}</span> @endif
            </a>

            <div class="my-2 border-t border-current/10"></div>

            <a href="{{ url('/settings') }}"
                class="flex min-h-12 items-center gap-3 rounded-2xl bg-current/5 px-4 py-3 font-medium transition-colors hover:bg-current/10">
                <x-heroicon-o-cog-6-tooth class="size-5" />
                <span>{{ __('Pengaturan') }}</span>
            </a>

            <form method="POST" action="{{ route('logout') }}" class="pt-2">
                @csrf
                <button type="submit"
                    class="flex min-h-12 w-full items-center justify-center gap-2 rounded-2xl border border-current/15 px-4 py-3 font-semibold text-[#ad3f45] transition-colors hover:bg-current/5 dark:text-[#ffafb1]">
                    <x-heroicon-o-arrow-right-start-on-rectangle class="size-5" />
                    <span>{{ __('Keluar dari Akun') }}</span>
                </button>
            </form>
        </div>
    </x-bottom-sheet>

    @livewireScripts
</body>
</html>
