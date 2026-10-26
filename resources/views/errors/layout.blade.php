<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') — {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite('resources/css/app.css')
</head>
<body class="grid min-h-screen place-items-center bg-surface px-4 py-10 text-ink">
    <main class="w-full max-w-md text-center">
        <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="mx-auto h-14 w-auto">

        <p class="mt-8 text-6xl font-extrabold text-brand-700" dir="ltr" aria-hidden="true">@yield('code')</p>
        <h1 class="mt-3 text-2xl font-extrabold">@yield('heading')</h1>
        <p class="mt-3 text-ink-soft">@yield('message')</p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            @yield('actions')
        </div>
    </main>
</body>
</html>