    {{-- ========== شريط المزايا ========== --}}
    <section aria-label="مزايا التسوق" class="border-y border-line bg-white">
        <ul class="mx-auto grid max-w-7xl grid-cols-2 gap-4 px-4 py-6 sm:px-6 lg:grid-cols-4 lg:px-8">
            @foreach ([
                ['truck', 'توصيل إلى بابك', 'نوصل طلبك أينما كنت'],
                ['banknote', 'الدفع عند الاستلام', 'ادفع بعد أن تستلم طلبك'],
                ['message-circle', 'تواصل عبر واتساب', 'نرد على استفساراتك مباشرة'],
                ['palette', 'مقاسات وألوان متعددة', 'اختر ما يناسبك بسهولة'],
            ] as [$icon, $title, $text])
                <li class="flex items-center gap-3">
                    <span class="grid size-11 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-700">
                        <x-dynamic-component :component="'lucide-' . $icon" class="size-5" aria-hidden="true" />
                    </span>
                    <span>
                        <span class="block text-sm font-extrabold">{{ $title }}</span>
                        <span class="block text-xs text-ink-soft">{{ $text }}</span>
                    </span>
                </li>
            @endforeach
        </ul>
    </section>
