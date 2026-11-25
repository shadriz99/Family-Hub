<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#28543e">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/svg+xml" href="/icons/icon-192.svg">
    <link rel="apple-touch-icon" href="/icons/icon-192.svg">
    <title>Family Hub · Pusat Kendali Keluarga</title>
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
</head>
<body class="min-h-full bg-[#f6f4ee] font-sans text-base text-[#263c32] antialiased transition-colors dark:bg-[#151f1b] dark:text-[#f3f1e8]">
    {{-- Skip Link for Keyboard Accessibility --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:rounded-xl focus:bg-[#28543e] focus:px-4 focus:py-2 focus:text-white focus:shadow-lg dark:focus:bg-[#bad6ae] dark:focus:text-[#151f1b]">
        {{ __('Lewati ke konten utama') }}
    </a>

    <div class="mx-auto flex min-h-dvh max-w-5xl flex-col px-5 py-6 sm:px-8 sm:py-8">
        {{-- Header --}}
        <header class="flex items-center justify-between border-b border-current/10 pb-5">
            <x-app-logo size="md" with-text subtitle="Pusat Kendali Keluarga" />

            <div class="flex items-center gap-2 sm:gap-3">
                <button type="button"
                    onclick="window.toggleDarkMode()"
                    aria-label="{{ __('Ganti tema') }}"
                    class="flex size-10 items-center justify-center rounded-2xl border border-current/15 text-current/80 transition-colors hover:bg-current/5">
                    <x-heroicon-o-moon class="size-5 hidden dark:inline" />
                    <x-heroicon-o-sun class="size-5 inline dark:hidden" />
                </button>

                @auth
                    @if (auth()->user()?->is_system_admin)
                        <a href="{{ route('admin.dashboard') }}"
                            class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-[#28543e] px-4 py-2 text-sm font-semibold text-white shadow-xs transition-opacity hover:opacity-90 dark:bg-[#bad6ae] dark:text-[#151f1b]">
                            {{ __('Panel Admin') }}
                        </a>
                    @else
                        <a href="{{ url('/') }}"
                            class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-[#28543e] px-4 py-2 text-sm font-semibold text-white shadow-xs transition-opacity hover:opacity-90 dark:bg-[#bad6ae] dark:text-[#151f1b]">
                            {{ __('Beranda') }}
                        </a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit"
                            class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-current/20 px-3.5 py-2 text-sm font-semibold transition-colors hover:bg-current/5">
                            {{ __('Keluar') }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}"
                        class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-[#28543e] px-5 py-2.5 text-sm font-semibold text-white shadow-xs transition-opacity hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current dark:bg-[#bad6ae] dark:text-[#151f1b]">
                        {{ __('Masuk') }}
                    </a>
                @endauth
            </div>
        </header>

        {{-- Hero Section --}}
        <main id="main-content" class="my-auto py-12 text-center sm:py-16">
            <span class="inline-flex items-center rounded-full border border-current/15 bg-current/5 px-3.5 py-1 text-xs font-semibold tracking-wide">
                {{ __('Pusat Manajemen Rumah Tangga') }}
            </span>

            <h1 class="mt-5 font-serif text-3xl font-bold tracking-tight sm:text-5xl lg:text-6xl text-balance">
                {{ __('Ruang Bersama untuk Pasangan & Keluarga') }}
            </h1>

            <p class="mx-auto mt-4 max-w-xl text-base leading-relaxed text-[#637067] sm:text-lg dark:text-[#b2beb2]">
                {{ __('Kelola keuangan bulanan, kalender agenda bersama, daftar belanja kolaboratif, dan inventaris aset keluarga dalam satu antarmuka privat.') }}
            </p>

            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                @auth
                    @if (auth()->user()?->is_system_admin)
                        <a href="{{ route('admin.dashboard') }}"
                            class="flex min-h-12 w-full items-center justify-center rounded-2xl bg-[#28543e] px-7 py-3 text-base font-semibold text-white shadow-sm transition-opacity hover:opacity-95 sm:w-auto dark:bg-[#bad6ae] dark:text-[#151f1b]">
                            {{ __('Buka Panel Administrator') }}
                        </a>
                    @else
                        <a href="{{ url('/') }}"
                            class="flex min-h-12 w-full items-center justify-center rounded-2xl bg-[#28543e] px-7 py-3 text-base font-semibold text-white shadow-sm transition-opacity hover:opacity-95 sm:w-auto dark:bg-[#bad6ae] dark:text-[#151f1b]">
                            {{ __('Buka Beranda Keluarga') }}
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}"
                        class="flex min-h-12 w-full items-center justify-center rounded-2xl bg-[#28543e] px-7 py-3 text-base font-semibold text-white shadow-sm transition-opacity hover:opacity-95 sm:w-auto dark:bg-[#bad6ae] dark:text-[#151f1b]">
                        {{ __('Masuk ke Akun Anda') }}
                    </a>
                @endauth
            </div>

            <p class="mt-4 text-xs text-[#637067] dark:text-[#b2beb2]">
                {{ __('Pendaftaran bersifat privat melalui undangan keluarga.') }}
            </p>

            {{-- Feature Pillars --}}
            <div class="mt-14 grid grid-cols-1 gap-5 text-left sm:grid-cols-3">
                <x-card :title="__('Keuangan Rumah Tangga')">
                    <p class="text-pretty text-sm leading-relaxed text-[#637067] dark:text-[#b2beb2]">
                        {{ __('Pencatatan pengeluaran cepat, alokasi anggaran, pemisahan tabungan operasional, dan laporan bulanan transparan.') }}
                    </p>
                </x-card>

                <x-card :title="__('Agenda & Kolaborasi')">
                    <p class="text-pretty text-sm leading-relaxed text-[#637067] dark:text-[#b2beb2]">
                        {{ __('Kalender bersama, tugas rutin bergantian, catatan penting, dan daftar belanja yang sinkron secara real-time.') }}
                    </p>
                </x-card>

                <x-card :title="__('Aset & Inventaris')">
                    <p class="text-pretty text-sm leading-relaxed text-[#637067] dark:text-[#b2beb2]">
                        {{ __('Pencatatan dokumen penting, garansi barang, jadwal pemeliharaan rumah, dan kontak teknisi darurat.') }}
                    </p>
                </x-card>
            </div>
        </main>

        {{-- Footer --}}
        <footer class="border-t border-current/10 pt-5 text-center text-xs text-[#637067] dark:text-[#b2beb2]">
            <p>{{ __('Family Hub — Berjalan privat di server rumah.') }}</p>
        </footer>
    </div>
</body>
</html>
