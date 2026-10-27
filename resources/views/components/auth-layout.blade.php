@props(['title', 'intro'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#28543e">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192.svg">
    <title>{{ $title }} · Family Hub</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-dvh bg-[#f6f4ee] text-[#263c32] antialiased dark:bg-[#151f1b] dark:text-[#f3f1e8]">
    <main class="mx-auto flex min-h-dvh max-w-md items-center px-5 py-10">
        <section class="w-full rounded-3xl border border-current/10 bg-[#fffefa] p-6 shadow-sm sm:p-8 dark:bg-[#1f2c25]" aria-labelledby="auth-title">
            <p class="mb-8 text-sm font-semibold tracking-wide">Family Hub</p>
            <h1 id="auth-title" class="font-serif text-3xl">{{ $title }}</h1>
            <p class="mt-3 mb-8 text-sm leading-relaxed text-[#637067] dark:text-[#b2beb2]">{{ $intro }}</p>
            {{ $slot }}
        </section>
    </main>
    @livewireScripts
</body>
</html>
