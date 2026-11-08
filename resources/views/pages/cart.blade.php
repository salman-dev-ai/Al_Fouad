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


new
 #[Title('سلة التسوق')]
class extends Component {
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


    <x-ui.breadcrumbs :items="[['label' => 'الرئيسية', 'url' => route('home')], ['label' => 'سلة التسوق']]" />

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

            @include('components.cart.lines')

            @include('components.cart.summary')
        </div>
    @endif

    {{-- مكمّلات مؤجَّلة لمحتويات السلة، لا تعيق تعديل الكميات ولا زر إتمام الطلب. --}}
<livewire:recommendations mode="cart" title="أكمل إطلالتك" lazy wire:key="cart-recs" />
</div>
