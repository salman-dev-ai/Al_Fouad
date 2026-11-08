    {{-- ───── الأسئلة الشائعة: عناصر details الأصلية (بلا JavaScript) ───── --}}
    @if ($show['faqs'])
        <section id="faqs" class="mx-auto max-w-3xl scroll-mt-24 px-4 pb-20 sm:px-6 sm:pb-28 lg:px-8 {{ $show['returns'] ? '' : 'pt-20 sm:pt-28' }}" aria-labelledby="faqs-heading">
            <x-ui.section-heading eyebrow="الأسئلة الشائعة" title="كل ما تودّ معرفته" :center="true" />
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
