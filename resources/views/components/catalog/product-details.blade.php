        {{-- ========== التفاصيل ========== --}}
        <div class="space-y-5">

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('products.index', ['category' => $product->category->slug]) }}" wire:navigate
                   class="text-sm font-semibold text-brand-700 hover:underline">{{ $product->category->name }}</a>

                @if ($variant)
                    <p role="status" class="ms-auto">
                        @if ($stock < 1)
                            <span class="inline-flex items-center gap-1 rounded-full bg-surface px-3 py-1 text-xs font-bold text-ink-soft">نفد من المخزون</span>
                        @elseif ($stock <= 5)
                            <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700">متبقي {{ $stock }} فقط</span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700">
                                <x-lucide-circle-check class="size-4" aria-hidden="true" /> متوفر في المخزون
                            </span>
                        @endif
                    </p>
                @endif
            </div>

            <div class="flex items-start justify-between gap-3">
                <h1 class="text-2xl font-extrabold leading-snug sm:text-3xl">{{ $product->name }}</h1>
                <x-ui.share-button :url="route('products.show', ['product' => $product->slug])" :title="$product->name" class="shrink-0 border border-line" />
            </div>

            {{-- ملخص التقييم (من أعمدة المنتج، بلا استعلام إضافي) --}}
            <a href="#reviews" class="inline-flex flex-wrap items-center gap-2 text-sm">
                @if ($product->rating_count > 0)
                    <x-ui.rating-stars :rating="(float) $product->rating_avg" size="size-5" />
                    <span class="font-extrabold">{{ number_format((float) $product->rating_avg, 1) }}</span>
                    <span class="text-ink-soft">({{ $product->rating_count }} تقييم)</span>
                @else
                    <span class="text-ink-soft underline underline-offset-4">لا توجد تقييمات بعد</span>
                @endif
            </a>

            @if ($description !== '')
                <div x-data="{ open: false }" class="text-sm leading-7 text-ink-soft">
                    <p class="whitespace-pre-line" :class="open ? '' : 'line-clamp-3'">{{ $description }}</p>
                    @if (mb_strlen($description) > 170)
                        <button type="button" @click="open = !open" :aria-expanded="open"
                                class="mt-1 font-bold text-brand-700 hover:underline" x-text="open ? 'عرض أقل' : 'اقرأ المزيد'">اقرأ المزيد</button>
                    @endif
                </div>
            @endif

            @if ($variant)
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <p class="text-3xl font-extrabold text-brand-700">{{ \App\Support\Money::format($variant->price, $currency) }}</p>
                    @if ($hasDiscount)
                        <s class="text-ink-soft">{{ \App\Support\Money::format($variant->compare_at_price, $currency) }}</s>
                        <span class="rounded-full bg-danger px-3 py-1 text-xs font-extrabold text-white">خصم {{ $discountPercent }}%</span>
                    @endif
                </div>
            @endif

            {{-- اللون --}}
            <fieldset>
                <legend class="mb-3 text-sm font-extrabold">اللون: <span class="font-normal text-ink-soft">{{ $color }}</span></legend>

                <div class="flex flex-wrap gap-3">
                    @foreach ($this->swatches as $swatch)
                        <label wire:key="color-{{ $loop->index }}" class="relative cursor-pointer" title="{{ $swatch['name'] }}">
                            <input type="radio" wire:model.live="color" value="{{ $swatch['name'] }}" class="peer sr-only">

                            @if ($swatch['hex'])
                                <span style="background-color: {{ $swatch['hex'] }}"
                                      class="relative grid size-10 place-items-center overflow-hidden rounded-full ring-1 ring-inset ring-black/20 transition hover:scale-110 motion-reduce:transition-none peer-checked:outline-2 peer-checked:outline-offset-2 peer-checked:outline-brand-600 peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-500 peer-checked:[&>svg]:opacity-100">
                                    <x-lucide-check class="size-5 opacity-0 transition {{ $swatch['light'] ? 'text-ink' : 'text-white' }}" aria-hidden="true" />

                                    @unless ($swatch['in_stock'])
                                        <span class="absolute h-0.5 w-12 rotate-45 bg-ink/60" aria-hidden="true"></span>
                                    @endunless

                                    <span class="sr-only">{{ $swatch['name'] }}@unless ($swatch['in_stock']) (نفد) @endunless</span>
                                </span>
                            @else
                                <span class="inline-block rounded-md border border-line px-4 py-2 text-sm hover:border-brand-500 peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:font-bold peer-checked:text-brand-700 peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-500">{{ $swatch['name'] }}</span>
                            @endif
                        </label>
                    @endforeach
                </div>
            </fieldset>

            {{-- المقاس: مقاسات اللون المحدد، والمنتهي معطّل --}}
            <fieldset>
                <legend class="mb-3 text-sm font-extrabold">المقاس: <span class="font-normal text-ink-soft">{{ $size }}</span></legend>
                <div class="flex flex-wrap gap-2">
                    @foreach ($this->variantsForColor as $option)
                        <label wire:key="size-{{ $color }}-{{ $loop->index }}">
                            <input type="radio" wire:model.live="size" value="{{ $option->size }}"
                                   @disabled($option->stock_quantity < 1) class="peer sr-only">
                            <span class="inline-block min-w-14 cursor-pointer rounded-xl border border-line px-4 py-2.5 text-center text-sm font-semibold hover:border-brand-500 peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:font-extrabold peer-checked:text-brand-700 peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-500 peer-disabled:cursor-not-allowed peer-disabled:text-ink-soft/50 peer-disabled:line-through peer-disabled:hover:border-line">{{ $option->size }}</span>
                        </label>
                    @endforeach
                </div>
                @error('variant') <p class="mt-2 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
            </fieldset>

            {{-- الكمية والإضافة: الكمية حالة محلية في Alpine، والخادم يعيد التحقق منها --}}
            <div x-data="{ qty: 1 }"
                 x-effect="qty = Math.min(Math.max(1, qty), Math.max(1, $wire.maxQuantity))"
                 class="space-y-4">
                <div class="flex items-center gap-4">
                    <span id="qty-label" class="text-sm font-extrabold">الكمية</span>
                    <div class="inline-flex items-center rounded-full border border-line bg-white" role="group" aria-labelledby="qty-label">
                        <button type="button" @click="qty = Math.max(1, qty - 1)" :disabled="qty <= 1"
                                aria-label="إنقاص الكمية" class="size-11 rounded-full disabled:opacity-40">
                            <x-lucide-minus class="mx-auto size-4" aria-hidden="true" />
                        </button>
                        <output class="min-w-10 text-center font-extrabold" x-text="qty" aria-live="polite">1</output>
                        <button type="button" @click="qty = Math.min($wire.maxQuantity, qty + 1)"
                                :disabled="qty >= $wire.maxQuantity"
                                aria-label="زيادة الكمية" class="size-11 rounded-full disabled:opacity-40">
                            <x-lucide-plus class="mx-auto size-4" aria-hidden="true" />
                        </button>
                    </div>
                </div>

                <div class="flex items-stretch gap-3">
                    <button type="button" @click="$wire.addToCart(qty)"
                            wire:loading.attr="disabled" wire:target="addToCart"
                            :disabled="$wire.maxQuantity < 1"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-brand-600 px-6 py-3.5 text-base font-extrabold text-white shadow-sm hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-ink-soft/40">
                        <x-lucide-shopping-cart class="size-5" aria-hidden="true" />
                        <span wire:loading.remove wire:target="addToCart">{{ $stock < 1 ? 'غير متوفر' : 'أضف إلى السلة' }}</span>
                        <span wire:loading wire:target="addToCart">جارٍ الإضافة…</span>
                    </button>

                    <x-catalog.favorite-button :product="$product" class="border border-line" />
                </div>

                @error('quantity') <p class="text-sm font-semibold text-danger" role="alert">{{ $message }}</p> @enderror

                @if ($added)
                    <p class="rounded-xl bg-brand-50 px-4 py-3 text-sm font-bold text-brand-700" role="status">
                        تمت إضافة المنتج إلى السلة.
                    </p>
                @endif
            </div>

            {{-- تنبيه التوفر: للخيار المحدد المنتهي فقط --}}
            @if ($variant && $stock < 1)
                <livewire:stock-alert :variant-id="$variant->id" wire:key="stock-alert-{{ $variant->id }}" />
            @endif
        </div>
