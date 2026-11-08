<?php

use App\Actions\About\BuildAboutPageData;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('قصتنا')] class extends Component {
    // يتأكد أن الصفحة منشورة وله محتوى، وإلا يعيد 404 (غير موجودة) للزائر.
    // البيانات تأتي من الكاش بلا استعلامات في الطلبات المتكررة.
    // الإدارة تتحكم بالنشر من «محتوى الصفحة».
    public function mount(): void
    {
        abort_unless($this->content['published'], 404);
    }

    // بيانات الصفحة الجاهزة (مصفوفة نصوص ومسارات)، وتُحسب مرة لكل طلب فقط.
    // مصدرها الكاش الذي يمسحه أي تعديل في الإدارة.
    // لا تحتوي كائنات Eloquent فلا N+1 ولا استعلام خفي في العرض.
    #[Computed]
    public function content(): array
    {
        return app(BuildAboutPageData::class)->cached();
    }
};
?>

@php
    $c = $this->content;
    $show = $c['show'];
    $store = $c['store'];
    $url = fn (?string $path): ?string => $path ? \Illuminate\Support\Facades\Storage::disk('public')->url($path) : null;

    // أصناف تنسيق نص Markdown (بدل إضافة Typography): كلها حروف ثابتة ليلتقطها Tailwind.
    $prose = '[&_p]:mt-4 [&_p]:leading-8 [&_h2]:mt-6 [&_h2]:text-xl [&_h2]:font-extrabold [&_h3]:mt-5 [&_h3]:font-extrabold [&_ul]:mt-4 [&_ul]:list-disc [&_ul]:ps-6 [&_ol]:mt-4 [&_ol]:list-decimal [&_ol]:ps-6 [&_li]:mt-1.5 [&_a]:font-semibold [&_a]:text-brand-700 [&_a]:underline [&_strong]:font-bold [&>*:first-child]:mt-0';

    $statCols = [1 => 'sm:grid-cols-1', 2 => 'sm:grid-cols-2', 3 => 'sm:grid-cols-3', 4 => 'sm:grid-cols-4'];
    $socialNames = ['facebook' => 'فيسبوك', 'instagram' => 'إنستغرام', 'tiktok' => 'تيك توك', 'x' => 'إكس', 'snapchat' => 'سناب شات', 'telegram' => 'تيليجرام'];

    // بيانات منظمة لمحركات البحث (متجر + أسئلة شائعة)، تُبنى من البيانات المعقّمة نفسها.
    $ld = [
        '@context' => 'https://schema.org',
        '@graph' => array_values(array_filter([
            array_filter([
                '@type' => 'Store',
                'name' => config('app.name'),
                'url' => url('/'),
                'description' => $c['seo_description'],
                'image' => $url($c['hero']['image']),
                'telephone' => $store['phone_digits'] ? '+' . $store['phone_digits'] : null,
                'email' => $store['email'],
                'address' => $store['address'] ? ['@type' => 'PostalAddress', 'streetAddress' => $store['address']] : null,
                'sameAs' => $store['social'] ? array_values($store['social']) : null,
            ]),
            $c['faqs'] ? [
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn ($faq) => [
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
                ], $c['faqs']),
            ] : null,
        ])),
    ];

    // هل تُعرض أول قيمة كبطاقة مميزة (عندما تكون القيم أكثر من اثنتين).
    $featureFirst = count($c['values'] ?? []) > 2;

    // أصناف مشتركة لأزرار الإجراء الصغيرة في الفروع.
    $chip = 'inline-flex items-center gap-1.5 rounded-full border border-line bg-white px-4 py-2 text-sm font-semibold transition hover:border-brand-500 hover:text-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500';
@endphp

{{-- وصف الصفحة لمحركات البحث: يظهر إن كان القالب يحوي @stack('head') داخل <head>. --}}
@push('head')
    @if ($c['seo_description'])
        <meta name="description" content="{{ $c['seo_description'] }}">
    @endif
@endpush

<div class="overflow-x-clip">
    {{-- ───── الغلاف: بطاقة كبيرة مستديرة داخل الصفحة ───── --}}
    @if ($show['hero'])
        <div class="mx-auto max-w-7xl px-3 pt-3 sm:px-6 sm:pt-5 lg:px-8">
            <section class="relative isolate overflow-hidden rounded-3xl bg-brand-700 text-white sm:rounded-[2.5rem]">
                @if ($c['hero']['image'])
                    <img src="{{ $url($c['hero']['image']) }}" alt="" width="1920" height="1080"
                         fetchpriority="high" decoding="async"
                         class="absolute inset-0 -z-20 size-full object-cover">
                    {{-- تدرج من جهة النص ليبقى القراءة واضحة فوق أي صورة --}}
                    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink/80 via-ink/40 to-ink/10 sm:bg-gradient-to-l sm:from-ink/80 sm:via-ink/50 sm:to-transparent"></div>
                @else
                    <div class="absolute -top-24 -end-24 -z-10 size-96 rounded-full bg-brand-500/40 blur-3xl"></div>
                    <div class="absolute -bottom-32 -start-16 -z-10 size-96 rounded-full bg-brand-900/50 blur-3xl"></div>
                @endif

                <div class="flex min-h-[26rem] flex-col justify-end px-6 pt-20 sm:min-h-[34rem] sm:justify-center sm:px-14 {{ $show['stats'] ? 'pb-28 sm:pb-32' : 'pb-12 sm:pb-16' }}">
                    <h1 class="max-w-3xl text-4xl font-black leading-[1.25] sm:text-6xl sm:leading-[1.2]">{{ $c['hero']['title'] ?? 'قصتنا' }}</h1>
                    @if ($c['hero']['subtitle'])
                        <p class="mt-5 max-w-xl text-base leading-8 text-white/85 sm:text-lg sm:leading-9">{{ $c['hero']['subtitle'] }}</p>
                    @endif
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('products.index') }}" wire:navigate
                           class="inline-flex items-center gap-2 rounded-full bg-white px-7 py-3.5 text-sm font-extrabold text-brand-700 transition hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                            تسوّق الآن
                            <x-lucide-arrow-left class="size-4 rtl:rotate-0" aria-hidden="true" />
                        </a>
                        @if ($store['whatsapp_digits'])
                            <a href="https://wa.me/{{ $store['whatsapp_digits'] }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-2 rounded-full border border-white/40 bg-white/10 px-7 py-3.5 text-sm font-bold text-white backdrop-blur transition hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                                <x-lucide-message-circle class="size-4" aria-hidden="true" /> تواصل عبر واتساب
                            </a>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    @else
        <header class="mx-auto max-w-7xl px-4 pt-14 sm:px-6 lg:px-8">
            <h1 class="text-4xl font-black sm:text-5xl">قصتنا</h1>
        </header>
    @endif

    {{-- ───── الأرقام: شريط طافٍ يتداخل مع الغلاف ───── --}}
    @if ($show['stats'])
        <section class="relative z-10 mx-auto max-w-6xl px-4 sm:px-6 {{ $show['hero'] ? '-mt-16 sm:-mt-20' : 'mt-10' }}" aria-label="أرقامنا">
            <dl class="grid grid-cols-2 overflow-hidden rounded-3xl bg-white shadow-xl shadow-ink/10 ring-1 ring-line {{ $statCols[min(4, count($c['stats']))] }} sm:divide-x sm:divide-x-reverse sm:divide-line max-sm:[&>div:nth-child(n+3)]:border-t max-sm:[&>div]:border-line">
                @foreach ($c['stats'] as $stat)
                    <div class="flex flex-col-reverse items-center gap-1 px-4 py-7 text-center sm:py-9" wire:key="stat-{{ $loop->index }}">
                        <dt class="text-sm font-medium text-ink-soft">{{ $stat['label'] }}</dt>
                        <dd class="text-3xl font-black tracking-tight text-brand-700 sm:text-4xl">{{ $stat['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endif

    {{-- ───── القصة والرسالة والرؤية ───── --}}
    @if ($show['story'])
        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 sm:py-28 lg:px-8" aria-labelledby="story-heading">
            <div @class(['grid items-center gap-12 lg:gap-20', 'lg:grid-cols-12' => $c['story']['image']])>
                <div @class(['lg:col-span-7' => $c['story']['image'], 'mx-auto max-w-3xl' => ! $c['story']['image']])>
                    <x-store.section-heading eyebrow="من نحن" :title="$c['story']['title'] ?? 'قصتنا'" />
                    <span id="story-heading" class="sr-only">{{ $c['story']['title'] ?? 'قصتنا' }}</span>

                    @if ($c['story']['body_html'])
                        <div class="mt-6 max-w-2xl text-lg text-ink-soft {{ $prose }}">{!! $c['story']['body_html'] !!}</div>
                    @endif
                </div>

                @if ($c['story']['image'])
                    <div class="relative lg:col-span-5">
                        {{-- مربع لوني خلف الصورة لإحساس بالعمق --}}
                        <div class="absolute -bottom-4 -end-4 -z-10 size-full rounded-[2rem] bg-brand-100 sm:-bottom-6 sm:-end-6" aria-hidden="true"></div>
                        <img src="{{ $url($c['story']['image']) }}" alt="" width="1200" height="1500"
                             loading="lazy" decoding="async"
                             class="aspect-[4/5] w-full rounded-[2rem] object-cover">
                    </div>
                @endif
            </div>

            @if ($c['story']['mission'] || $c['story']['vision'])
                <div class="mt-16 grid gap-5 md:grid-cols-2">
                    @if ($c['story']['mission'])
                        <div class="relative overflow-hidden rounded-3xl bg-brand-700 p-8 text-white sm:p-10">
                            <x-lucide-target class="absolute -bottom-6 -start-6 size-40 text-white/10" aria-hidden="true" />
                            <h3 class="text-2xl font-black">رسالتنا</h3>
                            <p class="relative mt-4 leading-9 text-white/90">{{ $c['story']['mission'] }}</p>
                        </div>
                    @endif
                    @if ($c['story']['vision'])
                        <div class="relative overflow-hidden rounded-3xl bg-brand-50 p-8 sm:p-10">
                            <x-lucide-eye class="absolute -bottom-6 -start-6 size-40 text-brand-700/10" aria-hidden="true" />
                            <h3 class="text-2xl font-black text-brand-700">رؤيتنا</h3>
                            <p class="relative mt-4 leading-9 text-ink-soft">{{ $c['story']['vision'] }}</p>
                        </div>
                    @endif
                </div>
            @endif
        </section>
    @endif

    {{-- ───── القيم والمميزات: شبكة غير متساوية (Bento) ───── --}}
    @if ($show['values'])
        <section class="bg-surface py-20 sm:py-28" aria-labelledby="values-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-store.section-heading eyebrow="لماذا نحن" title="قيمنا ومميزاتنا" :center="true" />
                <h2 id="values-heading" class="sr-only">قيمنا ومميزاتنا</h2>

                <ul class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($c['values'] as $value)
                        <li @class([
                                'group rounded-3xl p-7 transition sm:p-8',
                                'bg-brand-700 text-white sm:col-span-2' => $loop->first && $featureFirst,
                                'bg-white ring-1 ring-line hover:ring-brand-500' => ! ($loop->first && $featureFirst),
                            ])
                            wire:key="value-{{ $loop->index }}">
                            <span @class([
                                'grid size-14 place-items-center rounded-2xl',
                                'bg-white/15 text-white' => $loop->first && $featureFirst,
                                'bg-brand-50 text-brand-700' => ! ($loop->first && $featureFirst),
                            ])>
                                @svg('lucide-' . $value['icon'], 'size-7', ['aria-hidden' => 'true'])
                            </span>
                            <h3 class="mt-6 text-xl font-black">{{ $value['title'] }}</h3>
                            <p @class(['mt-2 max-w-lg leading-8', 'text-white/85' => $loop->first && $featureFirst, 'text-ink-soft' => ! ($loop->first && $featureFirst)])>{{ $value['text'] }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ───── شركاؤنا: شعارات دائرية متحركة ───── --}}
    @if ($show['partners'])
        @php
            $reps = max(1, (int) ceil(12 / count($c['partners'])));
            $set = collect(range(1, $reps))->flatMap(fn () => $c['partners'])->all();
            $seconds = max(25, min(90, count($set) * 4));
        @endphp

        <section class="py-20 sm:py-28" aria-labelledby="partners-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-store.section-heading eyebrow="شركاؤنا" title="نعمل مع نخبة من الشركاء" :center="true" />
                <h2 id="partners-heading" class="sr-only">شركاؤنا</h2>
            </div>

            <div class="marquee mt-12 overflow-hidden motion-reduce:overflow-x-auto [-webkit-mask-image:linear-gradient(to_right,transparent,#000_10%,#000_90%,transparent)] [mask-image:linear-gradient(to_right,transparent,#000_10%,#000_90%,transparent)]">
                <div class="marquee-track flex w-max items-center gap-10 pe-10" style="animation-duration: {{ $seconds }}s">
                    @foreach ([false, true] as $isCopy)
                        {{-- النسخة الثانية للحركة المتصلة فقط: مخفية عن قارئ الشاشة وغير قابلة للتركيز. --}}
                        <ul class="flex items-center gap-10" {{ $isCopy ? 'aria-hidden="true" inert' : '' }}>
                            @foreach ($set as $partner)
                                <li>
                                    @if ($partner['url'])
                                        <a href="{{ $partner['url'] }}" target="_blank" rel="noopener noreferrer nofollow"
                                           aria-label="{{ $partner['name'] }}"
                                           class="grid size-24 place-items-center rounded-full bg-surface p-5 ring-1 ring-line transition hover:bg-white hover:ring-brand-500 sm:size-28">
                                            <img src="{{ $url($partner['logo']) }}" alt="{{ $partner['name'] }}" width="80" height="80"
                                                 loading="lazy" decoding="async" class="size-full object-contain grayscale transition hover:grayscale-0">
                                        </a>
                                    @else
                                        <span class="grid size-24 place-items-center rounded-full bg-surface p-5 ring-1 ring-line sm:size-28">
                                            <img src="{{ $url($partner['logo']) }}" alt="{{ $partner['name'] }}" width="80" height="80"
                                                 loading="lazy" decoding="async" class="size-full object-contain grayscale">
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ───── آراء العملاء: شريط أفقي بتمرير لاصق ───── --}}
    @if ($show['testimonials'])
        <section class="bg-brand-700 py-20 text-white sm:py-28" aria-labelledby="testimonials-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
                 x-data="{
                     scroll(direction) {
                         const el = this.$refs.track;
                         const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                         el.scrollBy({ left: direction * el.clientWidth * 0.8, behavior: reduce ? 'auto' : 'smooth' });
                     },
                 }">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <h2 id="testimonials-heading" class="text-3xl font-black sm:text-4xl">ماذا يقول عملاؤنا</h2>
                        <p class="mt-3 text-white/75">تجارب حقيقية من أشخاص اشتروا منا.</p>
                    </div>

                    <div class="flex gap-2 max-sm:hidden">
                        <button type="button" @click="scroll(1)" aria-label="السابق"
                                class="grid size-12 place-items-center rounded-full border border-white/30 transition hover:bg-white hover:text-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                            <x-lucide-chevron-right class="size-5" aria-hidden="true" />
                        </button>
                        <button type="button" @click="scroll(-1)" aria-label="التالي"
                                class="grid size-12 place-items-center rounded-full border border-white/30 transition hover:bg-white hover:text-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                            <x-lucide-chevron-left class="size-5" aria-hidden="true" />
                        </button>
                    </div>
                </div>

                <ul x-ref="track" tabindex="0" role="region" aria-roledescription="شريط متحرك" aria-label="آراء العملاء"
                    class="mt-10 flex snap-x snap-mandatory gap-5 overflow-x-auto pb-4 [scrollbar-width:none] focus-visible:outline-3 focus-visible:outline-offset-4 focus-visible:outline-white">
                    @foreach ($c['testimonials'] as $testimonial)
                        <li class="w-[85%] shrink-0 snap-start sm:w-96" wire:key="testimonial-{{ $loop->index }}">
                            <figure class="flex h-full flex-col rounded-3xl bg-white p-7 text-ink">
                                <div class="flex items-center justify-between">
                                    <div class="flex gap-0.5" role="img" aria-label="التقييم {{ $testimonial['rating'] }} من 5">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <x-lucide-star :class="$i <= $testimonial['rating'] ? 'size-4 fill-brand-600 text-brand-600' : 'size-4 text-line'" aria-hidden="true" />
                                        @endfor
                                    </div>
                                    <x-lucide-quote class="size-8 text-brand-600/20" aria-hidden="true" />
                                </div>

                                <blockquote class="mt-5 flex-1 text-lg leading-9">{{ $testimonial['quote'] }}</blockquote>

                                <figcaption class="mt-6 flex items-center gap-3 border-t border-line pt-5">
                                    @if ($testimonial['avatar'])
                                        <img src="{{ $url($testimonial['avatar']) }}" alt="" width="48" height="48"
                                             loading="lazy" decoding="async" class="size-12 rounded-full object-cover">
                                    @else
                                        <span class="grid size-12 place-items-center rounded-full bg-brand-50 text-lg font-extrabold text-brand-700" aria-hidden="true">{{ mb_substr($testimonial['name'], 0, 1) }}</span>
                                    @endif
                                    <span>
                                        <span class="block font-extrabold">{{ $testimonial['name'] }}</span>
                                        @if ($testimonial['subtitle'])
                                            <span class="block text-sm text-ink-soft">{{ $testimonial['subtitle'] }}</span>
                                        @endif
                                    </span>
                                </figcaption>
                            </figure>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ───── فروعنا ───── --}}
    @if ($show['branches'])
        <section id="branches" class="mx-auto max-w-7xl scroll-mt-24 px-4 py-20 sm:px-6 sm:py-28 lg:px-8" aria-labelledby="branches-heading">
            <x-store.section-heading eyebrow="فروعنا" title="زورونا في أقرب فرع" :center="true" />
            <h2 id="branches-heading" class="sr-only">فروعنا</h2>

            <ul class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($c['branches'] as $branch)
                    <li class="group flex flex-col rounded-3xl bg-surface p-3" wire:key="branch-{{ $loop->index }}">
                        <div class="overflow-hidden rounded-2xl">
                            @if ($branch['image'])
                                <img src="{{ $url($branch['image']) }}" alt="" width="960" height="540"
                                     loading="lazy" decoding="async"
                                     class="aspect-video w-full object-cover transition duration-500 motion-reduce:transition-none group-hover:scale-105">
                            @else
                                <div class="grid aspect-video place-items-center bg-brand-50 text-brand-700" aria-hidden="true">
                                    <x-lucide-store class="size-10" />
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-1 flex-col px-3 pb-3 pt-5">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="text-lg font-black">{{ $branch['name'] }}</h3>
                                @if ($branch['city'])
                                    <p class="shrink-0 rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700">{{ $branch['city'] }}</p>
                                @endif
                            </div>
                            <p class="mt-3 flex gap-2 text-sm leading-7 text-ink-soft">
                                <x-lucide-map-pin class="mt-1 size-4 shrink-0" aria-hidden="true" /> <span>{{ $branch['address'] }}</span>
                            </p>
                            @if ($branch['hours_text'])
                                <p class="mt-1 flex gap-2 text-sm leading-7 text-ink-soft">
                                    <x-lucide-clock class="mt-1 size-4 shrink-0" aria-hidden="true" /> <span>{{ $branch['hours_text'] }}</span>
                                </p>
                            @endif

                            <div class="mt-auto flex flex-wrap gap-2 pt-5">
                                @if ($branch['phone_digits'])
                                    <a href="tel:+{{ $branch['phone_digits'] }}" class="{{ $chip }}">
                                        <x-lucide-phone class="size-4" aria-hidden="true" /> اتصال
                                    </a>
                                @endif
                                @if ($branch['whatsapp_digits'])
                                    <a href="https://wa.me/{{ $branch['whatsapp_digits'] }}" target="_blank" rel="noopener noreferrer" class="{{ $chip }}">
                                        <x-lucide-message-circle class="size-4" aria-hidden="true" /> واتساب
                                    </a>
                                @endif
                                @if ($branch['map_url'])
                                    <a href="{{ $branch['map_url'] }}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500">
                                        <x-lucide-navigation class="size-4" aria-hidden="true" /> الاتجاهات
                                    </a>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- ───── الدفع والتوصيل ───── --}}
    @if ($show['payments'])
        <section id="payments" class="scroll-mt-24 bg-surface py-20 sm:py-28" aria-labelledby="payments-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-store.section-heading eyebrow="الدفع والتوصيل" title="طرق الدفع والتوصيل" :center="true" />
                <h2 id="payments-heading" class="sr-only">طرق الدفع والتوصيل</h2>

                <div class="mt-12 grid gap-5 lg:grid-cols-2">
                    @if ($c['payments']['methods'])
                        <ul class="grid content-start gap-3">
                            @foreach ($c['payments']['methods'] as $method)
                                <li class="flex items-center gap-4 rounded-2xl bg-white p-5 ring-1 ring-line transition hover:ring-brand-500" wire:key="payment-{{ $loop->index }}">
                                    <span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-brand-50 text-brand-700">
                                        @svg('lucide-' . $method['icon'], 'size-6', ['aria-hidden' => 'true'])
                                    </span>
                                    <span>
                                        <span class="block font-extrabold">{{ $method['name'] }}</span>
                                        @if ($method['description'])
                                            <span class="mt-0.5 block text-sm leading-7 text-ink-soft">{{ $method['description'] }}</span>
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($c['payments']['shipping_html'])
                        <div class="rounded-3xl bg-white p-7 ring-1 ring-line sm:p-9">
                            <h3 class="flex items-center gap-3 text-xl font-black">
                                <span class="grid size-11 place-items-center rounded-2xl bg-brand-700 text-white"><x-lucide-truck class="size-5" aria-hidden="true" /></span>
                                التوصيل
                            </h3>
                            <div class="mt-4 text-ink-soft {{ $prose }}">{!! $c['payments']['shipping_html'] !!}</div>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- ───── سياسة الاسترجاع ───── --}}
    @if ($show['returns'])
        <section id="returns" class="mx-auto max-w-5xl scroll-mt-24 px-4 py-20 sm:px-6 sm:py-28 lg:px-8" aria-labelledby="returns-heading">
            <div class="grid overflow-hidden rounded-[2rem] bg-brand-50 md:grid-cols-12">
                @if ($c['returns']['days'])
                    <div class="flex flex-col items-center justify-center gap-1 bg-brand-700 px-6 py-10 text-center text-white md:col-span-4">
                        <x-lucide-rotate-ccw class="size-8 text-white/70" aria-hidden="true" />
                        <p class="mt-3 text-6xl font-black leading-none sm:text-7xl">{{ $c['returns']['days'] }}</p>
                        <p class="text-lg font-bold">{{ $c['returns']['days'] === 1 ? 'يوم' : 'أيام' }} للاسترجاع</p>
                    </div>
                @endif

                <div @class(['p-8 sm:p-10', 'md:col-span-8' => $c['returns']['days'], 'md:col-span-12' => ! $c['returns']['days']])>
                    <h2 id="returns-heading" class="flex items-center gap-3 text-2xl font-black">
                        @unless ($c['returns']['days'])
                            <x-lucide-rotate-ccw class="size-7 text-brand-700" aria-hidden="true" />
                        @endunless
                        سياسة الاسترجاع
                    </h2>

                    @if ($c['returns']['html'])
                        <div class="mt-4 text-ink-soft {{ $prose }}">{!! $c['returns']['html'] !!}</div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- ───── الأسئلة الشائعة: عناصر details الأصلية (بلا JavaScript) ───── --}}
    @if ($show['faqs'])
        <section id="faqs" class="mx-auto max-w-3xl scroll-mt-24 px-4 pb-20 sm:px-6 sm:pb-28 lg:px-8 {{ $show['returns'] ? '' : 'pt-20 sm:pt-28' }}" aria-labelledby="faqs-heading">
            <x-store.section-heading eyebrow="الأسئلة الشائعة" title="كل ما تودّ معرفته" :center="true" />
            <h2 id="faqs-heading" class="sr-only">الأسئلة الشائعة</h2>

            <div class="mt-12 space-y-3">
                @foreach ($c['faqs'] as $faq)
                    <details class="group rounded-2xl bg-surface p-5 transition open:bg-white open:ring-1 open:ring-brand-500 sm:p-6" wire:key="faq-{{ $loop->index }}">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 rounded-lg font-extrabold marker:content-none focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-500 [&::-webkit-details-marker]:hidden">
                            <span>{{ $faq['question'] }}</span>
                            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-white text-brand-700 ring-1 ring-line transition group-open:bg-brand-700 group-open:text-white group-open:ring-brand-700">
                                <x-lucide-plus class="size-4 transition-transform group-open:rotate-45" aria-hidden="true" />
                            </span>
                        </summary>
                        <p class="mt-4 whitespace-pre-line leading-8 text-ink-soft">{{ $faq['answer'] }}</p>
                    </details>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ───── معلومات التواصل ───── --}}
    @if ($show['contact'])
        <section id="contact" class="mx-auto max-w-7xl scroll-mt-24 px-4 pb-20 sm:px-6 sm:pb-28 lg:px-8 {{ $show['faqs'] ? '' : 'pt-20 sm:pt-28' }}" aria-labelledby="contact-heading">
            <div class="rounded-[2rem] bg-surface p-6 sm:p-12">
                <x-store.section-heading eyebrow="تواصل معنا" title="يسعدنا خدمتكم" :center="true" />
                <h2 id="contact-heading" class="sr-only">معلومات التواصل</h2>

                <div class="mt-10 grid gap-5 lg:grid-cols-2">
                    <div class="space-y-5 rounded-3xl bg-white p-7 ring-1 ring-line sm:p-8">
                        @if ($store['phone_digits'])
                            <p class="flex items-center gap-4"><span class="grid size-11 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-700"><x-lucide-phone class="size-5" aria-hidden="true" /></span>
                                <a href="tel:+{{ $store['phone_digits'] }}" class="font-bold hover:underline" dir="ltr">{{ $store['phone'] }}</a></p>
                        @endif
                        @if ($store['whatsapp_digits'])
                            <p class="flex items-center gap-4"><span class="grid size-11 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-700"><x-lucide-message-circle class="size-5" aria-hidden="true" /></span>
                                <a href="https://wa.me/{{ $store['whatsapp_digits'] }}" target="_blank" rel="noopener noreferrer" class="font-bold hover:underline">تواصل عبر واتساب</a></p>
                        @endif
                        @if ($store['email'])
                            <p class="flex items-center gap-4"><span class="grid size-11 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-700"><x-lucide-mail class="size-5" aria-hidden="true" /></span>
                                <a href="mailto:{{ $store['email'] }}" class="font-bold hover:underline" dir="ltr">{{ $store['email'] }}</a></p>
                        @endif
                        @if ($store['address'])
                            <p class="flex items-start gap-4"><span class="grid size-11 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-700"><x-lucide-map-pin class="size-5" aria-hidden="true" /></span>
                                <span class="pt-2 leading-8">{{ $store['address'] }}
                                    @if ($store['map_url'])
                                        <a href="{{ $store['map_url'] }}" target="_blank" rel="noopener noreferrer" class="ms-2 font-bold text-brand-700 underline underline-offset-4">الاتجاهات</a>
                                    @endif
                                </span></p>
                        @endif

                        @if ($store['social'])
                            <ul class="flex flex-wrap gap-2 border-t border-line pt-5" aria-label="حساباتنا">
                                @foreach ($store['social'] as $key => $link)
                                    <li><a href="{{ $link }}" target="_blank" rel="noopener noreferrer" class="{{ $chip }}">{{ $socialNames[$key] ?? $key }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    @if ($store['hours'])
                        <div class="rounded-3xl bg-brand-700 p-7 text-white sm:p-8">
                            <h3 class="flex items-center gap-3 text-xl font-black">
                                <x-lucide-clock class="size-6 text-white/70" aria-hidden="true" /> أوقات العمل
                            </h3>
                            <dl class="mt-5 divide-y divide-white/15 text-sm">
                                @foreach ($store['hours'] as $row)
                                    <div class="flex justify-between gap-4 py-3">
                                        <dt class="font-bold">{{ $row['day'] }}</dt>
                                        <dd class="text-white/80">
                                            @if ($row['closed'])
                                                مغلق
                                            @else
                                                <span dir="ltr">{{ $row['opens'] }} – {{ $row['closes'] }}</span>
                                            @endif
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- ───── دعوة ختامية ───── --}}
    <section class="mx-auto max-w-7xl px-3 pb-12 sm:px-6 sm:pb-16 lg:px-8">
        <div class="relative isolate overflow-hidden rounded-3xl bg-ink px-6 py-16 text-center text-white sm:rounded-[2.5rem] sm:py-20">
            <div class="absolute -top-24 start-1/2 -z-10 size-96 -translate-x-1/2 rounded-full bg-brand-600/50 blur-3xl" aria-hidden="true"></div>
            <h2 class="mx-auto max-w-2xl text-3xl font-black leading-snug sm:text-4xl">جاهز لاكتشاف مجموعتنا؟</h2>
            <a href="{{ route('products.index') }}" wire:navigate
               class="mt-8 inline-flex items-center gap-2 rounded-full bg-white px-9 py-4 text-base font-extrabold text-brand-700 transition hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                تسوّق الآن
                <x-lucide-arrow-left class="size-5" aria-hidden="true" />
            </a>
        </div>
    </section>

    {{-- بيانات منظمة لمحركات البحث: HEX تمنع أي نص من إغلاق وسم script. --}}
    <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
</div>
