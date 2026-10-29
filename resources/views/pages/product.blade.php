<?php

use App\Actions\Cart\AddItemToCart;
use App\Actions\Cart\ResolveCart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StoreSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

use App\Models\ProductColor;
 use Illuminate\Support\Facades\Cache;


new class extends Component {
    public int $productId;

    public string $color = '';
    /** ألوان بدرجاتها لعرضها كدوائر. */
#[Computed]
public function swatches(): Collection
{
    return ProductVariant::swatchesFrom($this->variants);
}

    public string $size = '';

    /** أقصى كمية يمكن طلبها للخيار المحدد (يقرأها Alpine لتحديد الأزرار). */
    public int $maxQuantity = 0;

    public bool $added = false;

    public function mount(Product $product): void
    {
        $this->productId = $product->id;

      // اللون المطلوب في الرابط (?color=) يُعتمد فقط إن وُجد بين ألوان المنتج، وإلا نعود للافتراضي.
// هكذا يفتح رابط المشاركة على اللون الذي شاركه صاحبه، ولا يؤثر أي قيمة مزوّرة.
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
        // تحميل الألوان مع المنتجات في الكتالوج والرئيسية
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


    /**
 * معرض كل لون: صوره أولًا (الرئيسية ثم الترتيب)، وإن لم تكن له صور فالصور العامة.
 * قائمة وليست مصفوفة بمفاتيح الأسماء، حتى لا تتحول أسماء مثل «42» إلى أرقام.
 *
 * @return array<int, array{name: string, images: array<int, array{url: string, alt: ?string}>}>
 */

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

    /** خيارات اللون المحدد (لكل منها مقاسها ومخزونها). */
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

    public function updatedColor(): void
    {
        $this->resetFeedback();

        // قيمة لا تخص المنتج: نعود لأول لون
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

<x-slot:description>{{ \Illuminate\Support\Str::limit(strip_tags((string) $this->product->description) ?: $this->product->name . ' من ' . config('app.name'), 155) }}</x-slot>

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
@endphp

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-store.breadcrumbs :items="[
        ['label' => 'الرئيسية', 'url' => route('home')],
        ['label' => 'المنتجات', 'url' => route('products.index')],
        ['label' => $product->category->name, 'url' => route('products.index', ['category' => $product->category->slug])],
        ['label' => $product->name],
    ]" />

    <div class="mt-6 grid gap-8 md:grid-cols-2 lg:gap-12">

        {{-- المعرض: حالة محلية في Alpine فقط --}}
      {{-- المعرض: كتلة لكل لون، والتبديل فوري في المتصفح بلا انتظار للخادم --}}
<div
    x-data="{
        active: 0,
        x0: 0,
        init() {
            // عند تغيير اللون نعود لأول صورة (وهي الرئيسية)
            this.$wire.$watch('color', () => { this.active = 0; });

            // تحميل مسبق لأول صورة من كل لون بعد خمول الصفحة، ليكون التبديل لحظيًا
            const urls = @js($firstUrls);
            const preload = () => urls.forEach((u) => { new Image().src = u; });
            'requestIdleCallback' in window ? requestIdleCallback(preload) : setTimeout(preload, 1500);
        },
    }"
    class="space-y-3"
>
    @foreach ($galleries as $gallery)
        @php
            $gImages = $gallery['images'];
            $count = count($gImages);
            $isCurrent = $gallery['name'] === $color;
        @endphp

        <div wire:key="gallery-{{ $loop->index }}"
             x-show="$wire.color === @js($gallery['name'])"
             @unless ($isCurrent) x-cloak @endunless
             @if ($count > 1)
                 @keydown.left.prevent="active = (active + 1) % {{ $count }}"
                 @keydown.right.prevent="active = (active + {{ $count - 1 }}) % {{ $count }}"
             @endif
             class="space-y-3">

            <div class="relative aspect-[3/4] overflow-hidden rounded-lg bg-brand-50"
                 @if ($count > 1)
                     @touchstart.passive="x0 = $event.changedTouches[0].clientX"
                     @touchend="const d = $event.changedTouches[0].clientX - x0; if (Math.abs(d) > 50) { active = (active + (d > 0 ? {{ $count - 1 }} : 1)) % {{ $count }}; }"
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

                @if ($count > 1)
                    <button type="button" @click="active = (active + {{ $count - 1 }}) % {{ $count }}"
                            aria-label="الصورة السابقة"
                            class="absolute right-2 top-1/2 grid size-9 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-ink shadow hover:bg-white">
                        <x-lucide-chevron-right class="size-5" aria-hidden="true" />
                    </button>
                    <button type="button" @click="active = (active + 1) % {{ $count }}"
                            aria-label="الصورة التالية"
                            class="absolute left-2 top-1/2 grid size-9 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-ink shadow hover:bg-white">
                        <x-lucide-chevron-left class="size-5" aria-hidden="true" />
                    </button>
                    <span class="absolute bottom-2 start-2 rounded-sm bg-ink/70 px-2 py-0.5 text-xs font-bold text-white"
                          aria-hidden="true" x-text="(active + 1) + ' / {{ $count }}'"></span>
                @endif
            </div>

            @if ($count > 1)
                <ul class="grid grid-cols-5 gap-2">
                    @foreach ($gImages as $i => $image)
                        <li>
                            <button type="button" @click="active = {{ $i }}"
                                    :aria-current="active === {{ $i }}" aria-label="عرض الصورة {{ $i + 1 }}"
                                    :class="active === {{ $i }} ? 'border-brand-600' : 'border-transparent'"
                                    class="block aspect-square w-full overflow-hidden rounded-md border-2">
                                <img src="{{ $image['url'] }}" alt="" loading="lazy" decoding="async" class="size-full object-cover">
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endforeach
</div>
        {{-- التفاصيل والاختيار --}}
        <div class="space-y-6">
            <div class="space-y-2">
                <a href="{{ route('products.index', ['category' => $product->category->slug]) }}" wire:navigate
                   class="text-sm font-semibold text-brand-700 hover:underline">{{ $product->category->name }}</a>
<div class="flex items-start justify-between gap-3">
    <h1 class="text-2xl font-extrabold sm:text-3xl">{{ $product->name }}</h1>

    <div class="flex shrink-0 items-center gap-2">
        <x-store.favorite-button :product="$product" class="border border-line" />
        <x-store.share-button :url="route('products.show', ['product' => $product->slug])" :title="$product->name" class="border border-line" />
    </div>
</div>            </div>

            @if ($variant)
                <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <p class="text-2xl font-extrabold text-brand-700">{{ \App\Support\Money::format($variant->price, $currency) }}</p>
                    @if ($hasDiscount)
                        <s class="text-ink-soft">{{ \App\Support\Money::format($variant->compare_at_price, $currency) }}</s>
                        <span class="rounded-sm bg-brand-700 px-2 py-0.5 text-xs font-bold text-white">خصم {{ $discountPercent }}%</span>
                    @endif
                </div>

                <p class="text-sm font-semibold" role="status">
                    @if ($variant->stock_quantity < 1)
                        <span class="text-ink-soft">نفد من المخزون</span>
                    @elseif ($variant->stock_quantity <= 5)
                        <span class="text-brand-700">متبقي {{ $variant->stock_quantity }} فقط</span>
                    @else
                        <span class="text-brand-700">متوفر</span>
                    @endif
                </p>
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
                    {{-- منتج قديم بلا درجة لون: نبقي الزر النصي --}}
                    <span class="inline-block rounded-md border border-line px-4 py-2 text-sm hover:border-brand-500 peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:font-bold peer-checked:text-brand-700 peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-500">{{ $swatch['name'] }}</span>
                @endif
            </label>
        @endforeach
    </div>
</fieldset>

            {{-- المقاس: يعرض مقاسات اللون المحدد، والمنتهي معطّل --}}
            <fieldset>
                <legend class="mb-2 text-sm font-extrabold">المقاس: <span class="font-normal text-ink-soft">{{ $size }}</span></legend>
                <div class="flex flex-wrap gap-2">
                    @foreach ($this->variantsForColor as $option)
                        <label wire:key="size-{{ $color }}-{{ $loop->index }}">
                            <input type="radio" wire:model.live="size" value="{{ $option->size }}"
                                   @disabled($option->stock_quantity < 1) class="peer sr-only">
                            <span class="inline-block min-w-14 cursor-pointer rounded-md border border-line px-4 py-2 text-center text-sm hover:border-brand-500 peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:font-bold peer-checked:text-brand-700 peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-500 peer-disabled:cursor-not-allowed peer-disabled:text-ink-soft/50 peer-disabled:line-through peer-disabled:hover:border-line">{{ $option->size }}</span>
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
                    <div class="inline-flex items-center rounded-md border border-line" role="group" aria-labelledby="qty-label">
                        <button type="button" @click="qty = Math.max(1, qty - 1)" :disabled="qty <= 1"
                                aria-label="إنقاص الكمية" class="size-10 disabled:opacity-40">
                            <x-lucide-minus class="mx-auto size-4" aria-hidden="true" />
                        </button>
                        <output class="min-w-10 text-center font-bold" x-text="qty" aria-live="polite">1</output>
                        <button type="button" @click="qty = Math.min($wire.maxQuantity, qty + 1)"
                                :disabled="qty >= $wire.maxQuantity"
                                aria-label="زيادة الكمية" class="size-10 disabled:opacity-40">
                            <x-lucide-plus class="mx-auto size-4" aria-hidden="true" />
                        </button>
                    </div>
                </div>

                <button type="button" @click="$wire.addToCart(qty)"
                        wire:loading.attr="disabled" wire:target="addToCart"
                        :disabled="$wire.maxQuantity < 1"
                        class="w-full rounded-md bg-brand-600 px-6 py-3.5 text-base font-extrabold text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-ink-soft/40 sm:w-auto sm:min-w-64">
                    <span wire:loading.remove wire:target="addToCart">
                        {{ ($variant?->stock_quantity ?? 0) < 1 ? 'غير متوفر' : 'أضف إلى السلة' }}
                    </span>
                    <span wire:loading wire:target="addToCart">جارٍ الإضافة…</span>
                </button>

                @error('quantity') <p class="text-sm font-semibold text-danger" role="alert">{{ $message }}</p> @enderror

                @if ($added)
                    <p class="rounded-md bg-brand-50 px-4 py-3 text-sm font-bold text-brand-700" role="status">
                        تمت إضافة المنتج إلى السلة.
                    </p>
                @endif
            </div>

            @if (filled($product->description))
                <div class="border-t border-line pt-6">
                    <h2 class="mb-2 text-base font-extrabold">الوصف</h2>
                    <p class="whitespace-pre-line text-ink-soft">{{ $product->description }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- اقتراحات مؤجَّلة: تُحمَّل بعد الصفحة ولا تتأثر بتبديل اللون. --}}
<livewire:store.recommendations mode="related" :product-id="$product->id" title="قد يعجبك أيضًا"
                                lazy wire:key="recs-{{ $product->id }}" />
</div>
