    {{-- ───── القصة والرسالة والرؤية ───── --}}
    @if ($show['story'])
        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 sm:py-28 lg:px-8" aria-labelledby="story-heading">
            <div @class(['grid items-center gap-12 lg:gap-20', 'lg:grid-cols-12' => $c['story']['image']])>
                <div @class(['lg:col-span-7' => $c['story']['image'], 'mx-auto max-w-3xl' => ! $c['story']['image']])>
                    <x-ui.section-heading eyebrow="من نحن" :title="$c['story']['title'] ?? 'قصتنا'" />
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
