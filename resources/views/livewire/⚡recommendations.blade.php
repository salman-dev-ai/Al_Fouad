<?php

use App\Actions\Cart\ResolveCart;
use App\Actions\Catalog\RecommendProducts;
use App\Models\StoreSetting;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public string $mode = 'related';

    #[Locked]
    public ?int $productId = null;

    public string $title = '';

    // نمط الاقتراح: related (مشابه لمنتج) أو cart (مكمّل للسلة)؛ المعاملان مقفلان فلا يغيّرهما المتصفح.
    public function mount(string $mode = 'related', ?int $productId = null, string $title = ''): void
    {
        $this->mode = $mode === 'cart' ? 'cart' : 'related';
        $this->productId = $productId;
        $this->title = $title;
    }

    #[Computed]
    public function currency(): string
    {
        return StoreSetting::current()?->currency_code ?? 'YER';
    }

    // الاقتراحات من RecommendProducts: للسلة من سلة الزائر نفسها، وللمنتج من معرّفه. فارغة = لا يُعرض شيء.
    #[Computed]
    public function products(): Collection
    {
        $recommend = app(RecommendProducts::class);

        if ($this->mode === 'cart') {
            $cart = app(ResolveCart::class)->find();

            return $cart ? $recommend->forCart($cart) : new Collection();
        }

        return $this->productId ? $recommend->relatedTo($this->productId) : new Collection();
    }
};
?>

@placeholder
    <div class="mt-12 rounded-3xl border border-line bg-white p-5 sm:p-6" aria-hidden="true">
        <div class="h-7 w-48 animate-pulse rounded bg-surface"></div>
        <div class="mt-6 flex gap-4 overflow-hidden">
            @foreach (range(1, 4) as $i)
                <div class="w-44 shrink-0 sm:w-52">
                    <div class="aspect-3/4 animate-pulse rounded-xl bg-surface"></div>
                    <div class="mt-3 h-4 w-3/4 animate-pulse rounded bg-surface"></div>
                </div>
            @endforeach
        </div>
    </div>
@endplaceholder

<div>
    @if ($this->products->isNotEmpty())
        <section class="mt-12 rounded-3xl border border-line bg-white p-5 shadow-sm sm:p-6"
                 aria-labelledby="recs-heading-{{ $mode }}"
                 x-data="{
                    scroll(dir) {
                        const el = $refs.track;
                        const rtl = getComputedStyle(el).direction === 'rtl' ? -1 : 1;
                        el.scrollBy({ left: dir * rtl * el.clientWidth * 0.8, behavior: 'smooth' });
                    },
                 }">
            <div class="flex items-center justify-between gap-3">
                <h2 id="recs-heading-{{ $mode }}" class="flex items-center gap-2 text-xl font-extrabold sm:text-2xl">
                    <x-lucide-sparkles class="size-6 text-brand-600" aria-hidden="true" />
                    {{ $title }}
                </h2>

                <div class="flex gap-2">
                    <button type="button" @click="scroll(-1)" aria-label="السابق" class="grid size-10 place-items-center rounded-full border border-line bg-white hover:bg-surface">
                        <x-lucide-chevron-right class="size-5" aria-hidden="true" />
                    </button>
                    <button type="button" @click="scroll(1)" aria-label="التالي" class="grid size-10 place-items-center rounded-full border border-line bg-white hover:bg-surface">
                        <x-lucide-chevron-left class="size-5" aria-hidden="true" />
                    </button>
                </div>
            </div>

            <ul x-ref="track" class="mt-6 flex snap-x snap-mandatory gap-4 overflow-x-auto pb-2 [scrollbar-width:none]">
                @foreach ($this->products as $product)
                    <li wire:key="rec-{{ $mode }}-{{ $product->id }}" class="w-44 shrink-0 snap-start sm:w-56 lg:w-60">
                        <x-catalog.product-card :product="$product" :currency="$this->currency" />
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
