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
    <x-ui.breadcrumbs :items="[
        ['label' => 'الرئيسية', 'url' => route('home')],
        ['label' => 'المنتجات', 'url' => route('products.index')],
        ['label' => $product->category->name, 'url' => route('products.index', ['category' => $product->category->slug])],
        ['label' => $product->name],
    ]" />

    <div class="mt-6 grid gap-8 lg:grid-cols-2 lg:gap-10">

        @include('components.catalog.product-gallery')

        <div class="xl:grid xl:grid-cols-[1fr_9.5rem] xl:gap-6">
            @include('components.catalog.product-details')

            @include('components.catalog.product-advantages')
        </div>
    </div>

    {{-- التقييمات: تُحمَّل فور اكتمال الصفحة (on-load) بدل انتظار الوصول إليها، ومعرّف المنتج مقفل داخل المكوّن. --}}
    <livewire:product-reviews :product-id="$product->id" lazy="on-load" wire:key="reviews-{{ $product->id }}" />

    {{-- اقتراحات مؤجَّلة: تُحمَّل عند الاقتراب منها ولا تتأثر بتبديل اللون. --}}
    <livewire:recommendations mode="related" :product-id="$product->id" title="منتجات قد تعجبك"
                                    lazy wire:key="recs-{{ $product->id }}" />
</div>
