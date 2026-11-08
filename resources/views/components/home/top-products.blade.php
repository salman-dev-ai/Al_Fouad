    {{-- ========== الأكثر مبيعًا (أو المميزة إن لم توجد مبيعات) ========== --}}
    @if ($topProducts->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8" aria-labelledby="top-heading">
            <div class="flex items-end justify-between gap-4">
                <h2 id="top-heading" class="text-2xl font-extrabold">{{ $topTitle }}</h2>
                <a href="{{ route('products.index') }}" wire:navigate
                   class="text-sm font-bold text-brand-700 underline underline-offset-4">عرض الكل</a>
            </div>

            <ul class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4 xl:grid-cols-6">
                @foreach ($topProducts as $product)
                    <li><x-catalog.product-card :product="$product" :currency="$currency" /></li>
                @endforeach
            </ul>
        </section>
    @endif
