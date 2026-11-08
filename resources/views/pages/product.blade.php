<?php

use App\Actions\Cart\AddItemToCart;
use App\Actions\Cart\ResolveCart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StoreSetting;
use App\Support\PhoneNumber;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public int $productId;

    public string $color = '';

    public string $size = '';

    // أقصى كمية للخيار المحدد (يقرأها Alpine لتحديد أزرار الكمية).
    public int $maxQuantity = 0;

    public bool $added = false;

    // يهيّئ الصفحة: اللون المطلوب في الرابط (?color=) يُعتمد فقط إن وُجد بين ألوان المنتج، وإلا الافتراضي.
    public function mount(Product $product): void
    {
        $this->productId = $product->id;

        $requested = request()->query('color');
        $pool = is_string($requested) && $this->variants->contains('color', $requested)
            ? $this->variants->where('color', $requested)
            : $this->variants;

        $default = $pool->first(fn (ProductVariant $v): bool => $v->stock_quantity > 0) ?? $pool->first();

        abort_if($default === null, 404);

        $this->color = $default->color;
        $this->size = $default->size;
        $this->syncSelection();
    }

    #[Computed]
    public function product(): Product
    {
        $product = Product::query()
            ->with(['category', 'variants', 'colors.images', 'generalImages'])
            ->findOrFail($this->productId);

        // المنتج المؤرشف أو التابع لتصنيف معطّل كأنه غير موجود
        abort_unless($product->is_active && $product->category->is_active, 404);

        return $product;
    }

    #[Computed]
    public function currency(): string
    {
        return StoreSetting::current()?->currency_code ?? 'YER';
    }

    #[Computed]
    public function variants(): Collection
    {
        $order = array_flip(ProductVariant::SIZES);

        return $this->product->variants
            ->sortBy(fn (ProductVariant $v): int => $order[$v->size] ?? 99)
            ->values();
    }

    #[Computed]
    public function colors(): Collection
    {
        return $this->variants->pluck('color')->unique()->values();
    }

    // ألوان بدرجاتها لعرضها كدوائر.
    #[Computed]
    public function swatches(): Collection
    {
        return ProductVariant::swatchesFrom($this->variants);
    }

    // معرض كل لون: صوره أولًا (الرئيسية ثم الترتيب)، وإن لم تكن له صور فالصور العامة.
    #[Computed]
    public function galleries(): array
    {
        $general = $this->product->generalImages;
        $byName = $this->product->colors->keyBy(fn ($color) => mb_strtolower($color->name));

        return $this->colors->map(function (string $name) use ($byName, $general): array {
            $images = $byName->get(mb_strtolower($name))?->images ?? collect();

            if ($images->isEmpty()) {
                $images = $general;
            }

            return [
                'name' => $name,
                'images' => $images
                    ->sortBy([['is_primary', 'desc'], ['sort_order', 'asc'], ['id', 'asc']])
                    ->map(fn ($image): array => ['url' => $image->url, 'alt' => $image->alt_text])
                    ->values()
                    ->all(),
            ];
        })->all();
    }

    // خيارات اللون المحدد (لكل منها مقاسها ومخزونها).
    #[Computed]
    public function variantsForColor(): Collection
    {
        return $this->variants->where('color', $this->color)->values();
    }

    #[Computed]
    public function selectedVariant(): ?ProductVariant
    {
        return $this->variants->first(
            fn (ProductVariant $v): bool => $v->color === $this->color && $v->size === $this->size
        );
    }

    // رابط واتساب للاستفسار عن هذا المنتج، أو null إن لم يضبط المتجر رقمًا.
    #[Computed]
    public function whatsappUrl(): ?string
    {
        $phone = PhoneNumber::normalize(StoreSetting::current()?->whatsapp_phone, config('store.default_country_code'));

        return $phone
            ? 'https://wa.me/' . PhoneNumber::digits($phone) . '?text=' . rawurlencode('مرحبًا، أستفسر عن: ' . $this->product->name)
            : null;
    }

    // مدة الاسترجاع بالأيام من إعدادات «من نحن» إن كانت مفعّلة عندكم، وإلا null فتُخفى البطاقة.
    #[Computed]
    public function returnDays(): ?int
    {
        return class_exists(\App\Support\AboutSettings::class) ? \App\Support\AboutSettings::returnDays() : null;
    }

    #[Computed]
    public function returnUrl(): ?string
    {
        return Route::has('about') ? route('about') . '#return-policy' : null;
    }

    public function updatedColor(): void
    {
        $this->resetFeedback();

        if ($this->variants->where('color', $this->color)->isEmpty()) {
            $this->color = $this->variants->first()->color;
        }

        $forColor = $this->variants->where('color', $this->color);
        $current = $forColor->firstWhere('size', $this->size);

        // إن لم يتوفر المقاس الحالي بهذا اللون ننتقل لأقرب مقاس متوفر
        if (! $current || $current->stock_quantity < 1) {
            $alternative = $forColor->first(fn (ProductVariant $v): bool => $v->stock_quantity > 0) ?? $forColor->first();
            $this->size = $alternative->size;
        }

        $this->syncSelection();
    }

    public function updatedSize(): void
    {
        $this->resetFeedback();

        if ($this->variants->where('color', $this->color)->where('size', $this->size)->isEmpty()) {
            $this->size = $this->variants->where('color', $this->color)->first()->size;
        }

        $this->syncSelection();
    }

    public function addToCart(int|string $quantity, AddItemToCart $addItem, ResolveCart $carts): void
    {
        $this->resetFeedback();

        $key = 'add-to-cart:' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, 40)) {
            $this->addError('quantity', 'محاولات كثيرة، حاول بعد قليل.');

            return;
        }

        RateLimiter::hit($key, 60);

        // الخيار يُحدَّد من بيانات الخادم، لا من أي رقم يرسله المتصفح
        $variant = $this->selectedVariant;

        if (! $variant) {
            $this->addError('variant', 'اختر اللون والمقاس.');

            return;
        }

        try {
            $addItem->execute($carts->findOrCreate(), $variant->id, (int) $quantity);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());

            return;
        }

        $this->added = true;
        $this->dispatch('cart-updated');
    }

    private function syncSelection(): void
    {
        // الخصائص المحسوبة تُحفظ طوال الطلب، فنمسحها بعد تغيّر الاختيار
        unset($this->variantsForColor, $this->selectedVariant);

        $this->maxQuantity = min(
            $this->selectedVariant?->stock_quantity ?? 0,
            AddItemToCart::MAX_QUANTITY
        );
    }

    private function resetFeedback(): void
    {
        $this->added = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        return $this->view()->title($this->product->name);
    }
};
?>

<x-slot:description>{{ Str::limit(strip_tags((string) $this->product->description) ?: $this->product->name . ' من ' . config('app.name'), 155) }}</x-slot:description>

@php
    $product = $this->product;
    $galleries = $this->galleries;
    $firstUrls = collect($galleries)->map(fn ($g) => $g['images'][0]['url'] ?? null)->filter()->unique()->values()->all();
    $variant = $this->selectedVariant;
    $currency = $this->currency;
    $hasDiscount = $variant?->hasDiscount() ?? false;
    $discountPercent = $hasDiscount
        ? (int) round((1 - (float) $variant->price / (float) $variant->compare_at_price) * 100)
        : 0;
    $description = trim((string) $product->description);
    $stock = (int) ($variant?->stock_quantity ?? 0);
@endphp

<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
    <x-store.breadcrumbs :items="[
        ['label' => 'الرئيسية', 'url' => route('home')],
        ['label' => 'المنتجات', 'url' => route('products.index')],
        ['label' => $product->category->name, 'url' => route('products.index', ['category' => $product->category->slug])],
        ['label' => $product->name],
    ]" />

    <div class="mt-6 grid gap-8 lg:grid-cols-2 lg:gap-10">

        {{-- ========== المعرض: كتلة لكل لون، والتبديل فوري في المتصفح ========== --}}
        <div
            x-data="{
                active: 0,
                x0: 0,
                init() {
                    // عند تغيير اللون نعود لأول صورة (وهي الرئيسية)
                    this.$wire.$watch('color', () => { this.active = 0; });

                    // تحميل مسبق لأول صورة من كل لون بعد خمول الصفحة ليكون التبديل لحظيًا
                    const urls = @js($firstUrls);
                    const preload = () => urls.forEach((u) => { new Image().src = u; });
                    'requestIdleCallback' in window ? requestIdleCallback(preload) : setTimeout(preload, 1500);
                },
            }"
        >
            @foreach ($galleries as $gallery)
                @php
                    $gImages = $gallery['images'];
                    $gCount = count($gImages);
                    $isCurrent = $gallery['name'] === $color;
                @endphp

                <div wire:key="gallery-{{ $loop->index }}"
                     x-show="$wire.color === @js($gallery['name'])"
                     @unless ($isCurrent) x-cloak @endunless
                     @if ($gCount > 1)
                         @keydown.left.prevent="active = (active + 1) % {{ $gCount }}"
                         @keydown.right.prevent="active = (active + {{ $gCount - 1 }}) % {{ $gCount }}"
                     @endif
                     class="flex flex-col gap-3 lg:flex-row">

                    {{-- الصورة الكبيرة (الأولى في الكود = يمين في RTL على الشاشات الكبيرة بعد المصغرات) --}}
                    <div class="relative order-1 aspect-4/5 flex-1 overflow-hidden rounded-2xl bg-brand-50 lg:order-2"
                         @if ($gCount > 1)
                             @touchstart.passive="x0 = $event.changedTouches[0].clientX"
                             @touchend="const d = $event.changedTouches[0].clientX - x0; if (Math.abs(d) > 50) { active = (active + (d > 0 ? {{ $gCount - 1 }} : 1)) % {{ $gCount }}; }"
                         @endif>
                        @forelse ($gImages as $i => $image)
                            <img x-show="active === {{ $i }}"
                                 @if ($i > 0) x-cloak @endif
                                 @if ($i > 0 || ! $isCurrent) loading="lazy" @else fetchpriority="high" @endif
                                 src="{{ $image['url'] }}" alt="{{ $image['alt'] ?: $product->name . ' - ' . $gallery['name'] }}"
                                 decoding="async" class="absolute inset-0 size-full object-cover">
                        @empty
                            <span class="flex size-full items-center justify-center text-brand-500/50" aria-hidden="true">
                                <x-lucide-shirt class="size-24" />
                            </span>
                        @endforelse

                        @if ($hasDiscount && $isCurrent)
                            <span class="absolute start-3 top-3 rounded-full bg-danger px-3 py-1 text-xs font-extrabold text-white shadow">خصم {{ $discountPercent }}%</span>
                        @endif

                        @if ($gCount > 1)
                            <button type="button" @click="active = (active + {{ $gCount - 1 }}) % {{ $gCount }}"
                                    aria-label="الصورة السابقة"
                                    class="absolute right-3 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/95 text-ink shadow hover:bg-white">
                                <x-lucide-chevron-right class="size-5" aria-hidden="true" />
                            </button>
                            <button type="button" @click="active = (active + 1) % {{ $gCount }}"
                                    aria-label="الصورة التالية"
                                    class="absolute left-3 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/95 text-ink shadow hover:bg-white">
                                <x-lucide-chevron-left class="size-5" aria-hidden="true" />
                            </button>

                            {{-- نقاط (للجوال خصوصًا) --}}
                            <div class="absolute inset-x-0 bottom-3 flex justify-center gap-1.5 lg:hidden" aria-hidden="true">
                                @foreach ($gImages as $i => $image)
                                    <span class="size-2 rounded-full transition" :class="active === {{ $i }} ? 'bg-white' : 'bg-white/50'"></span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- المصغرات: عمود جانبي على الشاشات الكبيرة، وصف أسفل الصورة على الجوال --}}
                    @if ($gCount > 1)
                        <ul class="order-2 flex gap-2 overflow-x-auto pb-1 lg:order-1 lg:max-h-[34rem] lg:w-20 lg:flex-col lg:overflow-y-auto lg:overflow-x-hidden lg:pb-0 [scrollbar-width:none]">
                            @foreach ($gImages as $i => $image)
                                <li class="w-16 shrink-0 lg:w-full">
                                    <button type="button" @click="active = {{ $i }}"
                                            :aria-current="active === {{ $i }}" aria-label="عرض الصورة {{ $i + 1 }}"
                                            :class="active === {{ $i }} ? 'border-brand-600' : 'border-transparent opacity-80 hover:opacity-100'"
                                            class="block aspect-square w-full overflow-hidden rounded-xl border-2 transition">
                                        <img src="{{ $image['url'] }}" alt="" width="80" height="80" loading="lazy" decoding="async" class="size-full object-cover">
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- ========== التفاصيل ========== --}}
        <div class="xl:grid xl:grid-cols-[1fr_9.5rem] xl:gap-6">
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
                    <x-store.share-button :url="route('products.show', ['product' => $product->slug])" :title="$product->name" class="shrink-0 border border-line" />
                </div>

                {{-- ملخص التقييم (من أعمدة المنتج، بلا استعلام إضافي) --}}
                <a href="#reviews" class="inline-flex flex-wrap items-center gap-2 text-sm">
                    @if ($product->rating_count > 0)
                        <x-store.rating-stars :rating="(float) $product->rating_avg" size="size-5" />
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

                        <x-store.favorite-button :product="$product" class="border border-line" />
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
                    <livewire:store.stock-alert :variant-id="$variant->id" wire:key="stock-alert-{{ $variant->id }}" />
                @endif
            </div>

            {{-- ========== عمود المزايا (من إعدادات المتجر فقط) ========== --}}
            <ul class="mt-6 grid grid-cols-2 gap-3 xl:mt-0 xl:grid-cols-1 xl:content-start" aria-label="مزايا الشراء">
                <li class="rounded-2xl border border-line bg-white p-3 text-center">
                    <span class="mx-auto grid size-11 place-items-center rounded-full bg-brand-50 text-brand-700"><x-lucide-truck class="size-5" aria-hidden="true" /></span>
                    <p class="mt-2 text-sm font-extrabold">توصيل إلى بابك</p>
                    <p class="text-xs text-ink-soft">نوصل طلبك أينما كنت</p>
                </li>
                <li class="rounded-2xl border border-line bg-white p-3 text-center">
                    <span class="mx-auto grid size-11 place-items-center rounded-full bg-brand-50 text-brand-700"><x-lucide-banknote class="size-5" aria-hidden="true" /></span>
                    <p class="mt-2 text-sm font-extrabold">الدفع عند الاستلام</p>
                    <p class="text-xs text-ink-soft">ادفع بعد أن تستلم</p>
                </li>
                @if ($this->returnDays)
                    <li class="rounded-2xl border border-line bg-white p-3 text-center">
                        <span class="mx-auto grid size-11 place-items-center rounded-full bg-brand-50 text-brand-700"><x-lucide-rotate-ccw class="size-5" aria-hidden="true" /></span>
                        <p class="mt-2 text-sm font-extrabold">
                            @if ($this->returnUrl)
                                <a href="{{ $this->returnUrl }}" class="hover:underline">استرجاع سهل</a>
                            @else
                                استرجاع سهل
                            @endif
                        </p>
                        <p class="text-xs text-ink-soft">خلال {{ $this->returnDays }} أيام</p>
                    </li>
                @endif
                @if ($this->whatsappUrl)
                    <li class="rounded-2xl border border-line bg-white p-3 text-center">
                        <span class="mx-auto grid size-11 place-items-center rounded-full bg-brand-50 text-brand-700"><x-lucide-headset class="size-5" aria-hidden="true" /></span>
                        <p class="mt-2 text-sm font-extrabold"><a href="{{ $this->whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="hover:underline">دعم العملاء</a></p>
                        <p class="text-xs text-ink-soft">عبر واتساب</p>
                    </li>
                @endif
            </ul>
        </div>
    </div>

    {{-- التقييمات: تُحمَّل فور اكتمال الصفحة (on-load) بدل انتظار الوصول إليها، ومعرّف المنتج مقفل داخل المكوّن. --}}
    <livewire:store.product-reviews :product-id="$product->id" lazy="on-load" wire:key="reviews-{{ $product->id }}" />

    {{-- اقتراحات مؤجَّلة: تُحمَّل عند الاقتراب منها ولا تتأثر بتبديل اللون. --}}
    <livewire:store.recommendations mode="related" :product-id="$product->id" title="منتجات قد تعجبك"
                                    lazy wire:key="recs-{{ $product->id }}" />
</div>
