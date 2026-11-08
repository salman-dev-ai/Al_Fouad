@php
    // أيقونة كل شبكة اجتماعية بحسب اسم الرابط (عربي أو إنجليزي)، وإن لم تُعرف فأيقونة رابط عامة.
    $socialIcon = function (string $label): string {
        $l = mb_strtolower($label);

        return match (true) {
            str_contains($l, 'facebook') || str_contains($l, 'فيس') => 'facebook',
            str_contains($l, 'instagram') || str_contains($l, 'انستا') || str_contains($l, 'إنستا') => 'instagram',
            str_contains($l, 'youtube') || str_contains($l, 'يوتيوب') => 'youtube',
            str_contains($l, 'twitter') || $l === 'x' || str_contains($l, 'تويتر') || str_contains($l, 'إكس') => 'twitter',
            str_contains($l, 'telegram') || str_contains($l, 'تيليجرام') || str_contains($l, 'تلجرام') => 'send',
            str_contains($l, 'linkedin') || str_contains($l, 'لينكد') => 'linkedin',
            default => 'link',
        };
    };

    $hoursRows = $hours();
    $socials = $socialLinks();
    $hasShopCol = true;
    $iconWrap = 'grid size-11 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-700 ring-1 ring-brand-100 transition group-hover:bg-brand-600 group-hover:text-white motion-reduce:transition-none';
    $heading = 'inline-block border-b-2 border-brand-500/40 pb-2 text-xl font-extrabold text-brand-800';
@endphp

<footer class="relative mt-24 px-3 pb-6 sm:px-6">
    <div class="relative mx-auto max-w-7xl rounded-[2.5rem] border border-brand-100 bg-white/90 px-5 pb-10 pt-24 shadow-[0_20px_50px_-25px_rgba(0,70,50,.35)] sm:px-10">

        {{-- زخارف الأوراق (زخرفة فقط) --}}
        <x-lucide-leaf class="pointer-events-none absolute -start-2 top-6 size-24 rotate-12 text-brand-500/25 max-sm:hidden" aria-hidden="true" />
        <x-lucide-sprout class="pointer-events-none absolute -end-2 top-6 size-24 -rotate-12 text-brand-500/25 max-sm:hidden" aria-hidden="true" />

        {{-- الشعار الدائري المتدلّي في الأعلى مع تجويف مقلوب يكمل حدّ البطاقة --}}
        <div class="absolute inset-x-0 top-0 -translate-y-1/2" aria-hidden="false">
            <svg class="pointer-events-none absolute left-1/2 top-1/2 h-auto w-[300px] -translate-x-1/2 sm:w-[340px]" viewBox="0 0 340 28" aria-hidden="true">
                <path d="M0 0H340C280 0 250 3 232 14C215 24 195 28 170 28C145 28 125 24 108 14C90 3 60 0 0 0Z" fill="#ffffff" transform="translate(0 28) scale(1 -1)" />
            </svg>

            <a href="{{ route('home') }}" wire:navigate aria-label="{{ config('app.name') }} - الصفحة الرئيسية"
               class="relative mx-auto grid size-24 place-items-center rounded-full bg-white shadow-xl ring-4 ring-brand-100 sm:size-28">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" width="95" height="40"
                     class="h-8 w-auto max-w-[75%] sm:h-10" loading="lazy">
            </a>
        </div>

        <div class="grid gap-10 md:grid-cols-3 md:gap-0">

            {{-- ===== تواصل معنا (العمود الأول: يمين في RTL) ===== --}}
            <section class="md:pe-10" aria-labelledby="ft-contact">
                <h2 id="ft-contact" class="{{ $heading }}">تواصل معنا</h2>

                <ul class="mt-6 space-y-4 text-sm">
                    @if ($telUrl())
                        <li>
                            <a href="{{ $telUrl() }}" class="group flex items-center gap-3 font-semibold text-ink hover:text-brand-700">
                                <span class="{{ $iconWrap }}"><x-lucide-phone class="size-5" aria-hidden="true" /></span>
                                <span dir="ltr">{{ $settings->phone }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($whatsappUrl())
                        <li>
                            <a href="{{ $whatsappUrl() }}" target="_blank" rel="noopener noreferrer" class="group flex items-center gap-3 font-semibold text-ink hover:text-brand-700">
                                <span class="{{ $iconWrap }}"><x-lucide-message-circle class="size-5" aria-hidden="true" /></span>
                                <span>تواصل عبر واتساب</span>
                            </a>
                        </li>
                    @endif

                    @if (filled($settings?->email))
                        <li>
                            <a href="mailto:{{ $settings->email }}" class="group flex items-center gap-3 font-semibold text-ink hover:text-brand-700">
                                <span class="{{ $iconWrap }}"><x-lucide-mail class="size-5" aria-hidden="true" /></span>
                                <span dir="ltr" class="break-all">{{ $settings->email }}</span>
                            </a>
                        </li>
                    @endif

                    @if (filled($settings?->address))
                        <li class="flex items-center gap-3">
                            <span class="{{ $iconWrap }}"><x-lucide-map-pin class="size-5" aria-hidden="true" /></span>
                            <span class="font-semibold">
                                {{ $settings->address }}
                                @if (filled($settings->map_url))
                                    <a href="{{ $settings->map_url }}" target="_blank" rel="noopener noreferrer"
                                       class="block text-xs font-bold text-brand-700 underline underline-offset-4 hover:text-brand-800">عرض على الخريطة</a>
                                @endif
                            </span>
                        </li>
                    @endif
                </ul>
            </section>

            {{-- ===== المتجر + الفروع + طرق الدفع ===== --}}
            <nav aria-label="روابط المتجر" class="md:border-s md:border-brand-100 md:px-10">
                <h2 class="{{ $heading }}">المتجر</h2>

                <ul class="mt-6 space-y-2.5 text-sm font-semibold text-ink">
                    <li><a href="{{ route('home') }}" wire:navigate class="hover:text-brand-700">الرئيسية</a></li>
                    <li><a href="{{ route('products.index') }}" wire:navigate class="hover:text-brand-700">المنتجات</a></li>

                    @if ($about['published'] ?? false)
                        <li><a href="{{ route('about') }}" wire:navigate class="hover:text-brand-700">قصتنا</a></li>

                        @if ($about['has_returns'] ?? false)
                            <li>
                                <a href="{{ route('about') }}#returns" class="hover:text-brand-700">سياسة الاسترجاع</a>
                                @if ($about['return_days'] ?? null)
                                    <span class="ms-1 rounded-full bg-brand-50 px-2 py-0.5 text-xs font-bold text-brand-700">{{ $about['return_days'] }} أيام</span>
                                @endif
                            </li>
                        @endif
                        @if ($about['has_payments'] ?? false)
                            <li><a href="{{ route('about') }}#payments" class="hover:text-brand-700">الدفع والتوصيل</a></li>
                        @endif
                        @if ($about['has_faqs'] ?? false)
                            <li><a href="{{ route('about') }}#faqs" class="hover:text-brand-700">الأسئلة الشائعة</a></li>
                        @endif
                    @endif
                </ul>

                @if (($about['published'] ?? false) && ($about['has_branches'] ?? false))
                    <h2 class="{{ $heading }} mt-8">فروعنا</h2>
                    <ul class="mt-5 space-y-2.5 text-sm font-semibold text-ink">
                        @foreach ($about['branches'] as $branch)
                            <li>
                                <a href="{{ route('about') }}#branches" class="flex items-center gap-2 hover:text-brand-700">
                                    <x-lucide-map-pin class="size-4 shrink-0 text-brand-600" aria-hidden="true" />
                                    <span>{{ $branch['name'] }}@if ($branch['city']) <span class="font-normal text-ink-soft">— {{ $branch['city'] }}</span>@endif</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if (($about['published'] ?? false) && ! empty($about['payment_names']))
                    <h2 class="{{ $heading }} mt-8">طرق الدفع</h2>
                    <ul class="mt-5 flex flex-wrap gap-2">
                        @foreach ($about['payment_names'] as $name)
                            <li class="inline-flex items-center gap-2 rounded-full border border-brand-300 bg-brand-50/60 px-4 py-1.5 text-xs font-bold text-brand-800">
                                <x-lucide-banknote class="size-4" aria-hidden="true" /> {{ $name }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </nav>

            {{-- ===== أوقات العمل ===== --}}
            @if ($hoursRows)
                <section class="md:border-s md:border-brand-100 md:ps-10" aria-labelledby="ft-hours">
                    <h2 id="ft-hours" class="{{ $heading }} flex w-fit items-center gap-2">
                        <x-lucide-clock class="size-5" aria-hidden="true" /> أوقات العمل
                    </h2>

                    <dl class="mt-6 space-y-2.5 text-sm">
                        @foreach ($hoursRows as $row)
                            <div class="flex justify-between gap-4">
                                <dt class="font-semibold text-ink">{{ $row['day'] }}</dt>
                                <dd dir="ltr" class="text-ink-soft">{{ $row['text'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endif
        </div>

        {{-- ===== تابعنا ===== --}}
        @if ($socials)
            <section class="mt-12" aria-labelledby="ft-social">
                <div class="flex items-center gap-4">
                    <span class="h-px flex-1 bg-brand-300/60" aria-hidden="true"></span>
                    <h2 id="ft-social" class="text-xl font-extrabold text-brand-800">تابعنا</h2>
                    <span class="h-px flex-1 bg-brand-300/60" aria-hidden="true"></span>
                </div>

                <ul class="mt-6 flex flex-wrap items-center justify-center gap-3 sm:gap-4">
                    @foreach ($socials as $label => $url)
                        <li>
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $label }}" title="{{ $label }}"
                               class="group grid size-12 place-items-center rounded-full bg-brand-50 text-brand-800 ring-1 ring-brand-100 transition hover:-translate-y-0.5 hover:bg-brand-600 hover:text-white motion-reduce:transition-none sm:size-14">
                                <x-dynamic-component :component="'lucide-' . $socialIcon((string) $label)" class="size-5 sm:size-6" aria-hidden="true" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>

    <p class="mt-6 text-center text-xs text-ink-soft">
        © {{ date('Y') }} {{ config('app.name') }}. جميع الحقوق محفوظة.
    </p>
</footer>
