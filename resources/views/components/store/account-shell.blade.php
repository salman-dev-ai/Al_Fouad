{{-- إطار مشترك لكل صفحات الحساب: عنوان + قائمة جانبية (أفقية قابلة للتمرير على الجوال) + المحتوى. --}}
{{-- أي قسم جديد (عناوين، طلبات، مفضلة) يُضاف رابطه هنا مرة واحدة فيظهر في كل الصفحات. --}}
{{-- تمييز الصفحة الحالية يتم في المتصفح بـ wire:current، فلا يضيع بعد إرسال النماذج. --}}
@props(['heading'])

<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-extrabold sm:text-3xl">{{ $heading }}</h1>

    <div class="mt-6 grid items-start gap-6 lg:grid-cols-[14rem_1fr]">
        <nav aria-label="قائمة الحساب" class="flex gap-2 overflow-x-auto pb-1 lg:flex-col lg:overflow-visible">
            @foreach ([
                ['account', 'نظرة عامة', 'layout-dashboard', true],
                ['account.profile', 'بياناتي', 'user', false],
                ['account.addresses', 'عناويني', 'map-pin', false],
                ['account.password', 'كلمة المرور', 'lock', false],
            ] as [$routeName, $label, $icon, $exact])
                <a href="{{ route($routeName) }}" wire:navigate
                   @if ($exact) wire:current.exact="bg-brand-50 text-brand-700" @else wire:current="bg-brand-50 text-brand-700" @endif
                   class="flex items-center gap-2 whitespace-nowrap rounded-md px-3 py-2 text-sm font-bold hover:bg-brand-50">
                    <x-dynamic-component :component="'lucide-' . $icon" class="size-4" aria-hidden="true" />
                    {{ $label }}
                </a>
            @endforeach

            <form method="POST" action="{{ route('logout') }}" class="lg:mt-3 lg:border-t lg:border-line lg:pt-3">
                @csrf
                <button type="submit" class="flex w-full items-center gap-2 whitespace-nowrap rounded-md px-3 py-2 text-sm font-bold text-ink-soft hover:bg-surface">
                    <x-lucide-log-out class="size-4" aria-hidden="true" />
                    تسجيل الخروج
                </button>
            </form>
        </nav>

        <div class="min-w-0">{{ $slot }}</div>
    </div>
</div>