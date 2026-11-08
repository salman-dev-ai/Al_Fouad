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

    {{-- بيانات الربط بصفحة «قصتنا» (مدخل كاش صغير، تُقرأ مرة واحدة وتُمرَّر للرأس والتذييل). --}}
@php($about = app(\App\Actions\About\BuildAboutPageData::class)->footer())
{{-- مكوّن مزامنة المفضلة (غير مرئي) --}}
<livewire:store.favorites />

    <a href="#main"
        class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-brand-700 focus:px-4 focus:py-2 focus:text-white">
        تخطَّ إلى المحتوى
    </a>

    <x-store.announcement-bar />

    <header class="sticky top-0 z-40 mb-9   ">
    {{-- الخلفية الخضراء بحافة سفلية مستديرة وأوراق زخرفية (قصّ داخلي حتى لا يُقصّ الشعار). --}}
    <div class="absolute inset-0 overflow-hidden rounded-b-[2rem] bg-[linear-gradient(180deg,#0b6b4b,#4CA542)] shadow-[0_10px_30px_-12px_rgba(0,70,50,.55)]" aria-hidden="true">
        {{-- <x-lucide-leaf class="absolute -start-3 -top-2 size-24 rotate-12 text-white/10 max-sm:hidden" />
        <x-lucide-sprout class="absolute -end-2 top-1 size-20 -rotate-12 text-white/10 max-sm:hidden" /> --}}
    </div>

    {{-- تجويف منحنٍ حول الشعار يكمل لون الشريط (زخرفة فقط). --}}
    <svg class="pointer-events-none absolute left-1/2 top-[calc(4.5rem-1px)] z-0 h-auto w-[300px] -translate-x-1/2 sm:w-[340px]"
         viewBox="0 0 340 28" fill="#004632" aria-hidden="true">
        <path d="M0 0H340C280 0 250 3 232 14C215 24 195 28 170 28C145 28 125 24 108 14C90 3 60 0 0 0Z" />
    </svg>

    <div class="relative z-10 mx-auto grid h-[4.5rem] max-w-7xl grid-cols-[1fr_auto_1fr] items-center gap-2 px-4 sm:px-6 lg:px-8">

        {{-- الجهة الأولى (يمين في RTL): تنقل --}}
        <nav aria-label="التنقل الرئيسي" class="flex items-center justify-start gap-1.5 sm:gap-3">
            <x-store.nav-icon :href="route('home')" icon="house" label="الرئيسية" :active="request()->routeIs('home')" />
            <x-store.nav-icon :href="route('products.index')" icon="shirt" label="المنتجات" :active="request()->routeIs('products.*')" />

            @if ($about['published'])
                <div class="max-md:hidden">
                    <x-store.nav-icon :href="route('about')" icon="book-heart" label="قصتنا" :active="request()->routeIs('about')" />
                </div>
            @endif

            {{-- المفضلة: العدّاد يتحدث فورًا عند الإضافة أو الإزالة --}}
            <x-store.nav-icon :href="route('favorites')" icon="heart" label="المفضلة" :active="request()->routeIs('favorites')"
                              x-data="{ n: window.Favorites?.count() ?? 0 }"
                              x-on:favorites-changed.window="n = window.Favorites.count()">
                <span x-show="n > 0" x-cloak x-text="n"
                      class="absolute -end-1.5 -top-1.5 min-w-5 rounded-full bg-brand-500 px-1 text-center text-xs font-bold leading-5 text-white ring-2 ring-[#004632]"></span>
            </x-store.nav-icon>
        </nav>

        {{-- الشعار الدائري في الوسط --}}
        <a href="{{ route('home') }}" wire:navigate aria-label="{{ config('app.name') }} - الصفحة الرئيسية"
           class="relative z-10 grid size-20 translate-y-6 place-items-center rounded-full bg-white shadow-xl ring-4 ring-white/25 transition hover:scale-105 motion-reduce:transition-none sm:size-24 sm:translate-y-5">
            <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" width="95" height="40"
                 class="h-7 w-auto max-w-[75%] sm:h-9">
        </a>

        {{-- الجهة الثانية (يسار في RTL): السلة والحساب --}}
        <div class="flex items-center justify-end gap-1.5 sm:gap-3">
            {{-- السلة: مكوّن الليفواير نفسه ملفوف بتسمية، وتُنسَّق أيقونته بالـ CSS (hdr-cart). --}}
            <div class="group hdr-cart relative">
                <livewire:store.cart-count />
                <span role="tooltip"
                      class="pointer-events-none absolute left-1/2 top-full z-50 mt-2 -translate-x-1/2 whitespace-nowrap rounded-lg bg-ink px-3 py-1.5 text-xs font-bold text-white opacity-0 shadow-lg transition motion-reduce:transition-none group-focus-within:opacity-100 group-hover:opacity-100">السلة</span>
            </div>

            @auth
                <x-store.nav-icon :href="route('account')" icon="circle-user-round" label="حسابي" :active="request()->routeIs('account*')" />
            @else
                <x-store.nav-icon :href="route('login')" icon="circle-user-round" label="تسجيل الدخول" :active="request()->routeIs('login')" />
            @endauth
        </div>
    </div>
</header>

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

<x-store.footer :about="$about" />
    @livewireScripts
</body>

</html>
