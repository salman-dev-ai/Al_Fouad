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

    // يحدد نمط الاقتراح: related (مشابه لمنتج) أو cart (مكمّل للسلة)، ويقبل غيره كـ related.
    // المعاملان مقفلان بعد التهيئة فلا يغيّرهما المتصفح.
    // العنوان نص عرض فقط.
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

    // يطلب الاقتراحات من RecommendProducts: للسلة من سلة الزائر نفسها (الكوكي)، وللمنتج من معرّفه.
    // لا يُمرَّر من المتصفح أي معرّف منتجات للسلة، فلا يمكن التلاعب بالنتائج.
    // مجموعة فارغة تعني لا يُعرض شيء.
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

{{-- هيكل التحميل: يظهر فورًا بدل القسم حتى يصل الرد، فلا تتأخر الصفحة. نوع العنصر الجذري يطابق المكوّن (div). --}}
@placeholder
    <div class="mt-12" aria-hidden="true">
        <div class="h-7 w-48 animate-pulse rounded bg-surface"></div>
        <div class="mt-6 flex gap-4 overflow-hidden lg:grid lg:grid-cols-4">
            @foreach (range(1, 4) as $i)
                <div class="w-44 shrink-0 lg:w-auto">
                    <div class="aspect-[3/4] animate-pulse rounded-lg bg-surface"></div>
                    <div class="mt-3 h-4 w-3/4 animate-pulse rounded bg-surface"></div>
                </div>
            @endforeach
        </div>
    </div>
@endplaceholder

{{-- قسم الاقتراحات: صف أفقي قابل للتمرير على الجوال، وشبكة من 4 أعمدة على الشاشات الكبيرة. --}}
{{-- لا يُرسم شيء إن لم توجد اقتراحات. --}}
<div>
    @if ($this->products->isNotEmpty())
        <section class="mt-12" aria-labelledby="recs-heading-{{ $mode }}">
            <h2 id="recs-heading-{{ $mode }}" class="text-xl font-extrabold sm:text-2xl">{{ $title }}</h2>

            <ul class="mt-6 flex snap-x gap-4 overflow-x-auto pb-2 lg:grid lg:grid-cols-4 lg:gap-6 lg:overflow-visible">
                @foreach ($this->products as $product)
                    <li wire:key="rec-{{ $mode }}-{{ $product->id }}" class="w-44 shrink-0 snap-start sm:w-52 lg:w-auto">
                        <x-store.product-card :product="$product" :currency="$this->currency" />
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
