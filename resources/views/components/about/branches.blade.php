    {{-- ───── فروعنا ───── --}}
    @if ($show['branches'])
        <section id="branches" class="mx-auto max-w-7xl scroll-mt-24 px-4 py-20 sm:px-6 sm:py-28 lg:px-8" aria-labelledby="branches-heading">
            <x-ui.section-heading eyebrow="فروعنا" title="زورونا في أقرب فرع" :center="true" />
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
