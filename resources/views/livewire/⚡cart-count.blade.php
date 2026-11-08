<?php

use App\Actions\Cart\ResolveCart;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    #[Computed]
    public function count(): int
    {
        $cart = app(ResolveCart::class)->find();

        return $cart ? (int) $cart->items()->sum('quantity') : 0;
    }

    #[On('cart-updated')]
    public function refreshCount(): void
    {
        unset($this->count);
    }
};
?>

<a href="{{ route('cart') }}" wire:navigate aria-label="سلة التسوق"
   class="relative inline-flex items-center rounded-md px-3 py-2 hover:bg-brand-50">
    <x-lucide-shopping-bag class="size-5" aria-hidden="true" />
    <span class="sr-only" role="status">{{ $this->count }} قطعة في السلة</span>

    @if ($this->count > 0)
        <span aria-hidden="true"
              class="absolute -top-0.5 -end-0.5 min-w-5 rounded-full bg-brand-700 px-1 text-center text-xs font-bold leading-5 text-white">
            {{ $this->count }}
        </span>
    @endif
</a>
