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
