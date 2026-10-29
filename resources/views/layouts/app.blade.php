<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ isset($title) ? $title . ' | ' : '' }}{{ config('app.name') }}</title>
    <!-- <meta name="description" content="{{ $description ?? 'تسوّق أحدث الملابس من متجر ' . config('app.name') }}">

    <link rel="icon" href="{{ asset('images/logo.webP') }}" type="image/png"> -->

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<meta name="theme-color" content="#3E8D35">
<link rel="canonical" href="{{ url()->current() }}">
<meta property="og:type" content="website">
<meta property="og:locale" content="ar_AR">
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:title" content="{{ $title ?? config('app.name') }}">
<meta property="og:description" content="{{ $description ?? '' }}">
<meta property="og:url" content="{{ url()->current() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="flex min-h-dvh flex-col bg-white text-ink antialiased">
{{-- مكوّن مزامنة المفضلة (غير مرئي) --}}
<livewire:store.favorites />

    <a href="#main"
        class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-brand-700 focus:px-4 focus:py-2 focus:text-white">
        تخطَّ إلى المحتوى
    </a>

    <x-store.announcement-bar />

    <header class="sticky top-0 z-40 border-b border-line bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" aria-label="{{ config('app.name') }} - الصفحة الرئيسية">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" width="95" height="40"
                    class="h-10 w-auto">
            </a>

            <nav aria-label="التنقل الرئيسي" class="flex items-center gap-1 text-sm font-semibold">
                <a href="{{ route('home') }}" wire:navigate @if (request()->routeIs('home')) aria-current="page" @endif
                    @class(['rounded-md px-3 py-2 hover:bg-brand-50', 'text-brand-700' => request()->routeIs('home')])>
                    الرئيسية
                </a>
                <a href="{{ route('products.index') }}" wire:navigate @if (request()->routeIs('products.*'))
                aria-current="page" @endif @class(['rounded-md px-3 py-2 hover:bg-brand-50', 'text-brand-700' => request()->routeIs('products.*')])>
                    المنتجات
                </a>

                {{-- رابط المفضلة مع عدّاد يتحدث فورًا عند إضافة أو إزالة أي منتج. --}}
<a href="{{ route('favorites') }}" wire:navigate
   x-data="{ n: window.Favorites?.count() ?? 0 }"
   @favorites-changed.window="n = window.Favorites.count()"
   class="relative inline-flex items-center rounded-md px-3 py-2 hover:bg-brand-50">
    <x-lucide-heart class="size-5" aria-hidden="true" />
    <span class="sr-only">المفضلة</span>
    <span x-show="n > 0" x-cloak x-text="n"
          class="absolute -top-0.5 -end-0.5 min-w-5 rounded-full bg-brand-700 px-1 text-center text-xs font-bold leading-5 text-white"></span>
</a>

                <livewire:store.cart-count />

                {{-- يعرض «حسابي» للمسجّل و«دخول» للزائر. --}}
@auth
    <a href="{{ route('account') }}" wire:navigate class="rounded-md px-3 py-2 text-sm font-bold hover:bg-brand-50">حسابي</a>
@else
    <a href="{{ route('login') }}" wire:navigate class="rounded-md px-3 py-2 text-sm font-bold hover:bg-brand-50">دخول</a>
@endauth
            </nav>

        </div>
    </header>

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

    <x-store.footer />

    @livewireScripts
</body>

</html>
