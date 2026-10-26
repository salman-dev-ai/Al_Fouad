<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ isset($title) ? $title . ' | ' : '' }}{{ config('app.name') }}</title>
    <meta name="description" content="{{ $description ?? 'تسوّق أحدث الملابس من متجر ' . config('app.name') }}">

    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-dvh flex-col bg-white text-ink antialiased">
    <a href="#main"
       class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-brand-700 focus:px-4 focus:py-2 focus:text-white">
        تخطَّ إلى المحتوى
    </a>

    <x-store.announcement-bar />

    <header class="sticky top-0 z-40 border-b border-line bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" aria-label="{{ config('app.name') }} - الصفحة الرئيسية">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}"
                     width="95" height="40" class="h-10 w-auto">
            </a>
        </div>
    </header>

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

    <x-store.footer />

    @livewireScripts
</body>
</html>
