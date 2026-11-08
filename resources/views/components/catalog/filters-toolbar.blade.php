        {{-- ========== صف الأدوات ========== --}}
        <div class="mt-5 flex flex-wrap items-center gap-3 rounded-2xl border border-line bg-white p-3 shadow-sm sm:p-4">
            <div class="relative min-w-full flex-1 sm:min-w-64">
                <label for="search" class="sr-only">ابحث عن منتج</label>
                <x-lucide-search class="pointer-events-none absolute inset-s-3 top-1/2 size-5 -translate-y-1/2 text-ink-soft" aria-hidden="true" />
                <input id="search" type="search" wire:model.live.debounce.300ms="search" maxlength="100" autocomplete="off"
                       placeholder="ابحث عن منتج..."
                       class="w-full rounded-xl border border-line bg-white py-2.5 ps-10 pe-3 text-sm focus:border-brand-500">
            </div>

            <p class="px-1 text-sm text-ink-soft" aria-live="polite">
                <strong class="text-base text-ink">{{ $this->products->total() }}</strong> نتيجة
            </p>

            <div class="relative">
                <label for="sort" class="sr-only">ترتيب النتائج</label>
                <x-lucide-arrow-up-down class="pointer-events-none absolute inset-s-3 top-1/2 size-4 -translate-y-1/2" aria-hidden="true" />
                <select id="sort" wire:model.live="sort"
                        class="appearance-none rounded-xl border border-line bg-white py-2.5 ps-9 pe-9 text-sm font-semibold focus:border-brand-500">
                    @foreach ($this->sortOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <x-lucide-chevron-down class="pointer-events-none absolute inset-e-3 top-1/2 size-4 -translate-y-1/2" aria-hidden="true" />
            </div>

            @if ($hasRange)
                <button type="button" @click="open.price = !open.price" :aria-expanded="open.price" aria-controls="panel-price" class="{{ $btn }}">
                    <x-lucide-coins class="size-4" aria-hidden="true" /> السعر
                    @if ($priceActive) <span class="size-2 rounded-full bg-brand-600" aria-label="مفعّل"></span> @endif
                    <x-lucide-chevron-down class="size-4 transition" ::class="open.price && 'rotate-180'" aria-hidden="true" />
                </button>
            @endif

            @if (count($facets['colors']) > 0)
                <button type="button" @click="open.color = !open.color" :aria-expanded="open.color" aria-controls="panel-color" class="{{ $btn }}">
                    <x-lucide-palette class="size-4" aria-hidden="true" /> اللون
                    @if ($color !== '') <span class="size-2 rounded-full bg-brand-600" aria-label="مفعّل"></span> @endif
                    <x-lucide-chevron-down class="size-4 transition" ::class="open.color && 'rotate-180'" aria-hidden="true" />
                </button>
            @endif

            @if (count($facets['sizes']) > 0)
                <button type="button" @click="open.size = !open.size" :aria-expanded="open.size" aria-controls="panel-size" class="{{ $btn }}">
                    <x-lucide-shirt class="size-4" aria-hidden="true" /> المقاس
                    @if ($size !== '') <span class="size-2 rounded-full bg-brand-600" aria-label="مفعّل"></span> @endif
                    <x-lucide-chevron-down class="size-4 transition" ::class="open.size && 'rotate-180'" aria-hidden="true" />
                </button>
            @endif

            <button type="button" wire:click="$toggle('inStockOnly')" aria-pressed="{{ $inStockOnly ? 'true' : 'false' }}"
                    @class([$btn, 'border-brand-600! bg-brand-50! text-brand-700' => $inStockOnly])>
                <x-lucide-package-check class="size-4" aria-hidden="true" /> المتوفر فقط
            </button>

            @if ($this->hasFilters)
                <button type="button" wire:click="clearFilters"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-50 px-4 py-2.5 text-sm font-bold text-brand-700 hover:bg-brand-100">
                    مسح الفلاتر
                    <x-lucide-rotate-ccw class="size-4" aria-hidden="true" />
                </button>
            @endif
        </div>
