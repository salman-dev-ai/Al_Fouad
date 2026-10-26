@props(['product', 'currency'])

@php
    $cheapest = $product->cheapestVariant();
    $image = $product->main_image;
    $inStock = $product->in_stock;
    $hasDiscount = $cheapest?->hasDiscount() ?? false;
    $discountPercent = $hasDiscount
        ? (int) round((1 - (float) $cheapest->price / (float) $cheapest->compare_at_price) * 100)
        : 0;
    $pricesDiffer = $product->variants->pluck('price')->unique()->count() > 1;
    $colors = $product->available_colors->count();
    $colorsLabel = match (true) {
        $colors <= 1 => 'لون واحد',
        $colors === 2 => 'لونان',
        $colors <= 10 => $colors . ' ألوان',
        default => $colors . ' لونًا',
    };
@endphp

<article {{ $attributes->class(['group relative flex flex-col']) }}>
    <a href="{{ route('products.show', ['product' => $product->slug]) }}" wire:navigate
        class="relative block aspect-3/4 overflow-hidden rounded-lg bg-brand-50">
        @if ($image)
            <img src="{{ $image->url }}" alt="{{ $image->alt_text ?: $product->name }}" loading="lazy" decoding="async"
                class="size-full object-cover transition duration-300 group-hover:scale-105 motion-reduce:transition-none">
        @else
            <span class="flex size-full items-center justify-center text-brand-500/50" aria-hidden="true">
                <x-lucide-shirt class="size-14" />
            </span>
        @endif

        @if (!$inStock)
            <span class="absolute inset-s-2 top-2 rounded-sm bg-ink/85 px-2 py-0.5 text-xs font-bold text-white">نفد</span>
        @elseif ($hasDiscount)
            <span class="absolute inset-s-2 top-2 rounded-sm bg-brand-700 px-2 py-0.5 text-xs font-bold text-white">خصم
                {{ $discountPercent }}%</span>
        @endif
    </a>

    <div class="mt-3 space-y-1">
        @if ($product->category)
            <p class="text-xs text-ink-soft">{{ $product->category->name }}</p>
        @endif

        <h3 class="line-clamp-2 text-sm font-bold sm:text-base">
            <a href="{{ route('products.show', ['product' => $product->slug]) }}" wire:navigate
                class="after:absolute after:inset-0 hover:text-brand-700">{{ $product->name }}</a>
        </h3>

        @if ($cheapest)
            <p class="flex flex-wrap items-baseline gap-x-2 text-sm">
                @if ($pricesDiffer)
                    <span class="text-xs text-ink-soft">ابتداءً من</span>
                @endif
                <span
                    class="font-extrabold text-brand-700">{{ \App\Support\Money::format($cheapest->price, $currency) }}</span>
                @if ($hasDiscount)
                    <s
                        class="text-xs text-ink-soft">{{ \App\Support\Money::format($cheapest->compare_at_price, $currency) }}</s>
                @endif
            </p>
            <p class="text-xs text-ink-soft">{{ $colorsLabel }}</p>
        @else
            <p class="text-sm text-ink-soft">غير متوفر حاليًا</p>
        @endif
    </div>
</article>
