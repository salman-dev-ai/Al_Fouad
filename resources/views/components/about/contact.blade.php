    {{-- ───── معلومات التواصل ───── --}}
    @if ($show['contact'])
        <section id="contact" class="mx-auto max-w-7xl scroll-mt-24 px-4 pb-20 sm:px-6 sm:pb-28 lg:px-8 {{ $show['faqs'] ? '' : 'pt-20 sm:pt-28' }}" aria-labelledby="contact-heading">
            <div class="rounded-[2rem] bg-surface p-6 sm:p-12">
                <x-ui.section-heading eyebrow="تواصل معنا" title="يسعدنا خدمتكم" :center="true" />
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
