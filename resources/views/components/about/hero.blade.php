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
