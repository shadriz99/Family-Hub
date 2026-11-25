<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#28543e">
    <link rel="icon" type="image/svg+xml" href="/icons/icon-192.svg">
    <link rel="apple-touch-icon" href="/icons/icon-192.svg">
    <title>{{ $title ?? __('Admin') }} · Family Hub</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
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
    @livewireStyles
</head>
<body class="min-h-full bg-[#f6f4ee] text-[#263c32] antialiased dark:bg-[#151f1b] dark:text-[#f3f1e8]">
    {{-- Skip Link for Keyboard Accessibility --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:rounded-xl focus:bg-[#28543e] focus:px-4 focus:py-2 focus:text-white focus:shadow-lg dark:focus:bg-[#bad6ae] dark:focus:text-[#151f1b]">
        {{ __('Lewati ke konten utama') }}
    </a>

    {{-- Header --}}
    <header class="border-b border-current/10 bg-[#fffefa]/80 backdrop-blur dark:bg-[#1f2c25]/80 sticky top-0 z-20">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3 sm:px-6">
            <div class="flex items-center gap-3">
                <x-app-logo size="xs" with-text subtitle="Panel Manajemen" />
                <span class="rounded-full bg-amber-500/10 px-2.5 py-0.5 text-xs font-semibold text-amber-600 dark:text-amber-400">
                    {{ __('Admin Panel') }}
                </span>
            </div>

            <div class="flex items-center gap-3 text-sm">
                <button type="button"
                    onclick="window.toggleDarkMode()"
                    aria-label="{{ __('Ganti tema') }}"
                    class="flex size-9 items-center justify-center rounded-xl border border-current/15 text-current/80 transition-colors hover:bg-current/5">
                    <x-heroicon-o-moon class="size-4 hidden dark:inline" />
                    <x-heroicon-o-sun class="size-4 inline dark:hidden" />
                </button>

                <span class="hidden sm:inline text-xs text-[#637067] dark:text-[#b2beb2]">
                    {{ auth()->user()?->name }} ({{ auth()->user()?->email }})
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="rounded-xl border border-current/20 px-3 py-1.5 text-xs font-semibold transition-colors hover:bg-current/5">
                        {{ __('Keluar') }}
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- Main Content --}}
    <main id="main-content" class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
