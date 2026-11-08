        {{-- ========== النتائج ========== --}}
        <section class="mt-8" aria-label="نتائج المنتجات">
            <p wire:loading class="sr-only" role="status">جارٍ تحديث النتائج…</p>

            @if ($this->products->isEmpty())
                <div class="rounded-2xl border border-dashed border-line bg-surface px-6 py-16 text-center">
                    <x-lucide-search-x class="mx-auto size-10 text-ink-soft" aria-hidden="true" />
                    <h2 class="mt-4 text-lg font-extrabold">لا توجد منتجات مطابقة</h2>
                    <p class="mt-1 text-sm text-ink-soft">جرّب تغيير كلمات البحث أو تخفيف الفلاتر.</p>
                    <button type="button" wire:click="clearFilters"
                            class="mt-5 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-700">
                        مسح الفلاتر
                    </button>
                </div>
            @else
                <div wire:loading.class="opacity-60"
                     class="grid grid-cols-2 gap-x-4 gap-y-8 transition-opacity sm:gap-x-6 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($this->products as $product)
                        <x-catalog.product-card :product="$product" :currency="$this->currency" wire:key="product-{{ $product->id }}" />
                    @endforeach
                </div>

                <div class="mt-10">
                    {{ $this->products->onEachSide(0)->links('vendor.pagination.simple-tailwind') }}
                </div>
            @endif
        </section>
