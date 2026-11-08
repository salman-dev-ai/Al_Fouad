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
