    {{-- ───── الدفع والتوصيل ───── --}}
    @if ($show['payments'])
        <section id="payments" class="scroll-mt-24 bg-surface py-20 sm:py-28" aria-labelledby="payments-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-ui.section-heading eyebrow="الدفع والتوصيل" title="طرق الدفع والتوصيل" :center="true" />
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
