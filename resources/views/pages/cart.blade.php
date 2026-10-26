<?php

use App\Actions\Cart\AddItemToCart;
use App\Actions\Cart\RemoveCartItem;
use App\Actions\Cart\ResolveCart;
use App\Actions\Cart\SummarizeCart;
use App\Actions\Cart\UpdateCartItem;
use App\Models\StoreSetting;
use App\Support\CartSummary;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('سلة التسوق')] class extends Component {
    #[Computed]
    public function currency(): string
    {
        return StoreSetting::current()?->currency_code ?? 'YER';
    }

    #[Computed]
    public function summary(): ?CartSummary
    {
        $cart = app(ResolveCart::class)->find();

        return $cart ? app(SummarizeCart::class)->execute($cart) : null;
    }

    public function changeQuantity(int $itemId, int $delta, UpdateCartItem $update, ResolveCart $carts): void
    {
        $this->resetErrorBag();

        $cart = $carts->find();
        $item = $cart?->items()->whereKey($itemId)->first();

        if (! $item) {
            return;
        }

        // الواجهة ترسل خطوة واحدة (+1 أو −1) فقط
        $step = $delta >= 0 ? 1 : -1;

        try {
            $update->execute($cart, $itemId, $item->quantity + $step);
        } catch (ValidationException $e) {
            $this->addError("item.{$itemId}", $e->validator->errors()->first());

            return;
        }

        $this->refreshCart();
    }

    public function remove(int $itemId, RemoveCartItem $remove, ResolveCart $carts): void
    {
        $this->resetErrorBag();

        if ($cart = $carts->find()) {
            $remove->execute($cart, $itemId);
        }

        $this->refreshCart();
    }

    private function refreshCart(): void
    {
        unset($this->summary);
        $this->dispatch('cart-updated');
    }
};
?>

@php
    $summary = $this->summary;
    $currency = $this->currency;
    $max = \App\Actions\Cart\AddItemToCart::MAX_QUANTITY;
@endphp

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-store.breadcrumbs :items="[['label' => 'الرئيسية', 'url' => route('home')], ['label' => 'سلة التسوق']]" />

    <h1 class="mt-4 text-2xl font-extrabold sm:text-3xl">سلة التسوق</h1>

    @if (! $summary || $summary->isEmpty())
        <div class="mt-8 rounded-lg border border-dashed border-line bg-surface px-6 py-16 text-center">
            <x-lucide-shopping-bag class="mx-auto size-10 text-ink-soft" aria-hidden="true" />
            <h2 class="mt-4 text-lg font-extrabold">سلتك فارغة</h2>
            <p class="mt-1 text-sm text-ink-soft">أضف بعض المنتجات لتبدأ طلبك.</p>
            <a href="{{ route('products.index') }}" wire:navigate
               class="mt-5 inline-block rounded-md bg-brand-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-700">
                تصفّح المنتجات
            </a>
        </div>
    @else
        <div class="mt-6 grid items-start gap-8 lg:grid-cols-[1fr_22rem]">

            <ul wire:loading.class="opacity-60" class="divide-y divide-line rounded-lg border border-line transition-opacity">
                @foreach ($summary->lines as $line)
                    @php
                        $item = $line['item'];
                        $variant = $line['variant'];
                        $product = $variant->product;
                        $image = $product->main_image;
                        $canIncrease = $line['quantity'] < min($variant->stock_quantity, $max);
                    @endphp

                    <li wire:key="line-{{ $item->id }}" class="flex gap-4 p-4">
                        <a href="{{ route('products.show', ['product' => $product->slug]) }}" wire:navigate
                           class="block w-20 shrink-0 sm:w-24">
                            <span class="block aspect-[3/4] overflow-hidden rounded-md bg-brand-50">
                                @if ($image)
                                    <img src="{{ $image->url }}" alt="{{ $image->alt_text ?: $product->name }}"
                                         loading="lazy" decoding="async" class="size-full object-cover">
                                @else
                                    <span class="flex size-full items-center justify-center text-brand-500/50" aria-hidden="true">
                                        <x-lucide-shirt class="size-8" />
                                    </span>
                                @endif
                            </span>
                        </a>

                        <div class="flex min-w-0 flex-1 flex-col gap-2">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ route('products.show', ['product' => $product->slug]) }}" wire:navigate
                                       class="line-clamp-2 font-bold hover:text-brand-700">{{ $product->name }}</a>
                                    <p class="text-sm text-ink-soft">{{ $variant->color }} / {{ $variant->size }}</p>
                                    <p class="text-xs text-ink-soft">سعر القطعة: {{ \App\Support\Money::formatMinor($line['unit_minor'], $currency) }}</p>
                                </div>
                                <p class="whitespace-nowrap font-extrabold text-brand-700">
                                    {{ \App\Support\Money::formatMinor($line['line_minor'], $currency) }}
                                </p>
                            </div>

                            @if ($line['issue'])
                                <p class="text-sm font-semibold text-danger" role="alert">{{ $line['issue'] }}</p>
                            @endif

                            @error("item.{$item->id}")
                                <p class="text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                            @enderror

                            <div class="mt-auto flex items-center justify-between gap-3">
                                <div class="inline-flex items-center rounded-md border border-line" role="group"
                                     aria-label="كمية {{ $product->name }}">
                                    <button type="button" wire:click="changeQuantity({{ $item->id }}, -1)"
                                            @disabled($line['quantity'] <= 1) aria-label="إنقاص الكمية"
                                            class="size-9 disabled:opacity-40">
                                        <x-lucide-minus class="mx-auto size-4" aria-hidden="true" />
                                    </button>
                                    <output class="min-w-9 text-center text-sm font-bold">{{ $line['quantity'] }}</output>
                                    <button type="button" wire:click="changeQuantity({{ $item->id }}, 1)"
                                            @disabled(! $canIncrease) aria-label="زيادة الكمية"
                                            class="size-9 disabled:opacity-40">
                                        <x-lucide-plus class="mx-auto size-4" aria-hidden="true" />
                                    </button>
                                </div>

                                <button type="button" wire:click="remove({{ $item->id }})"
                                        class="flex items-center gap-1 text-sm font-semibold text-ink-soft hover:text-danger">
                                    <x-lucide-trash-2 class="size-4" aria-hidden="true" />
                                    حذف
                                </button>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            {{-- ملخص الطلب --}}
            <aside class="space-y-4 rounded-lg border border-line bg-surface p-5 lg:sticky lg:top-24" aria-labelledby="summary-heading">
                <h2 id="summary-heading" class="text-lg font-extrabold">ملخص الطلب</h2>

                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-soft">المجموع الفرعي</dt>
                        <dd class="font-semibold">{{ \App\Support\Money::formatMinor($summary->subtotalMinor, $currency) }}</dd>
                    </div>

                    @if ($summary->taxEnabled)
                        <div class="flex justify-between gap-3">
                            <dt class="text-ink-soft">الضريبة ({{ rtrim(rtrim($summary->taxRate, '0'), '.') }}%)</dt>
                            <dd class="font-semibold">{{ \App\Support\Money::formatMinor($summary->taxMinor, $currency) }}</dd>
                        </div>
                    @endif

                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-soft">رسوم التوصيل</dt>
                        <dd class="font-semibold">
                            {{ $summary->deliveryFeeMinor > 0 ? \App\Support\Money::formatMinor($summary->deliveryFeeMinor, $currency) : 'مجانًا' }}
                        </dd>
                    </div>

                    <div class="flex justify-between gap-3 border-t border-line pt-3 text-base">
                        <dt class="font-extrabold">الإجمالي</dt>
                        <dd class="font-extrabold text-brand-700">{{ \App\Support\Money::formatMinor($summary->totalMinor, $currency) }}</dd>
                    </div>
                </dl>

                @if ($summary->hasBlockingIssues())
                    <p class="rounded-md border border-danger/30 bg-white px-3 py-2 text-sm font-semibold text-danger" role="alert">
                        في سلتك عناصر تحتاج تعديلًا قبل إتمام الطلب.
                    </p>
                @endif
                
                @if ($summary->hasBlockingIssues())
                    <span class="block cursor-not-allowed rounded-md bg-ink-soft/40 px-5 py-3 text-center text-base font-extrabold text-white" aria-disabled="true">إتمام الطلب</span>
                @else
                    <a href="{{ route('checkout') }}" wire:navigate
                       class="block rounded-md bg-brand-600 px-5 py-3 text-center text-base font-extrabold text-white hover:bg-brand-700">
                        إتمام الطلب
                    </a>
                @endif
                <p class="text-xs text-ink-soft">الدفع عند الاستلام. الأسعار المعروضة محسوبة من الخادم وقد تتغير قبل تأكيد الطلب.</p>

                <a href="{{ route('products.index') }}" wire:navigate
                   class="block text-center text-sm font-semibold text-brand-700 underline underline-offset-4">
                    متابعة التسوق
                </a>
            </aside>
        </div>
    @endif
</div>
