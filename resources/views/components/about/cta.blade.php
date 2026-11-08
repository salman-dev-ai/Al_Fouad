    {{-- ───── دعوة ختامية ───── --}}
    <section class="mx-auto max-w-7xl px-3 pb-12 sm:px-6 sm:pb-16 lg:px-8">
        <div class="relative isolate overflow-hidden rounded-3xl bg-ink px-6 py-16 text-center text-white sm:rounded-[2.5rem] sm:py-20">
            <div class="absolute -top-24 start-1/2 -z-10 size-96 -translate-x-1/2 rounded-full bg-brand-600/50 blur-3xl" aria-hidden="true"></div>
            <h2 class="mx-auto max-w-2xl text-3xl font-black leading-snug sm:text-4xl">جاهز لاكتشاف مجموعتنا؟</h2>
            <a href="{{ route('products.index') }}" wire:navigate
               class="mt-8 inline-flex items-center gap-2 rounded-full bg-white px-9 py-4 text-base font-extrabold text-brand-700 transition hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                تسوّق الآن
                <x-lucide-arrow-left class="size-5" aria-hidden="true" />
            </a>
        </div>
    </section>
