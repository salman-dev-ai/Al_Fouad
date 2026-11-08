    {{-- ───── القيم والمميزات: شبكة غير متساوية (Bento) ───── --}}
    @if ($show['values'])
        <section class="bg-surface py-20 sm:py-28" aria-labelledby="values-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-ui.section-heading eyebrow="لماذا نحن" title="قيمنا ومميزاتنا" :center="true" />
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
