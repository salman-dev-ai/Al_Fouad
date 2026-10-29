<?php

use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('المفضلة')] class extends Component {
    public array $guestIds = [];

    public bool $loaded = false;

    // يستقبل معرّفات مفضلة الزائر من متصفحه (غير موثوقة): تُنظَّف لأعداد صحيحة فريدة وتُقيَّد بـ 100.
    // لا تكشف شيئًا غير منتجات عامة معروضة أصلًا في المتجر.
    // المسجّل لا يحتاجها (يقرأ من قاعدة البيانات).
    public function loadGuest(array $ids): void
    {
        $this->guestIds = collect($ids)
            ->filter(fn ($id) => is_int($id) || (is_string($id) && ctype_digit($id)))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take(100)
            ->values()
            ->all();

        $this->loaded = true;
    }

    #[Computed]
    public function currency(): string
    {
        return StoreSetting::current()?->currency_code ?? 'YER';
    }

    // المنتجات المفضلة المعروضة فعلًا فقط (مفعّلة وتصنيفها مفعّل ولها خيارات)، بتحميل مسبق لتفادي N+1.
    // المسجّل: من حسابه والأحدث إضافةً أولًا. الزائر: من المعرّفات المنظّفة أعلاه.
    #[Computed]
    public function products(): Collection
    {
        $user = auth()->user();

        $query = $user
            ? $user->favoriteProducts()->orderByPivot('created_at', 'desc')
            : Product::query()->whereIn('id', $this->guestIds ?: [0])->orderByDesc('id');

        return $query
            ->active()
            ->whereHas('category', fn ($q) => $q->active())
            ->whereHas('variants')
            ->with(['category:id,name', 'images', 'variants', 'colors:id,product_id,sort_order'])
            ->withMin('variants', 'price')
            ->get();
    }
};
?>

@php
    $currency = $this->currency;
    $ready = auth()->check() || $loaded;
@endphp

{{-- صفحة المفضلة: للمسجّل من قاعدة البيانات، وللزائر من معرّفات متصفحه. --}}
{{-- إزالة منتج بالقلب تخفي بطاقته فورًا بلا طلب للخادم، وتظهر رسالة «فارغة» عند إزالة الجميع. --}}
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8"
     x-data="{ empty: false }"
     @if (auth()->guest()) x-init="$wire.loadGuest(window.Favorites?.all() ?? [])" @endif
     @favorites-changed.window="empty = [...$el.querySelectorAll('[data-fav]')].every((el) => !window.Favorites.has(el.dataset.fav))">

    <h1 class="text-2xl font-extrabold sm:text-3xl">المفضلة</h1>

    @if (! $ready)
        <p class="mt-8 text-ink-soft" role="status">جارٍ التحميل…</p>
    @elseif ($this->products->isEmpty())
        <div class="mt-8 rounded-lg border border-dashed border-line p-10 text-center">
            <p class="text-ink-soft">لم تضف أي منتج إلى المفضلة بعد. اضغط على القلب في أي منتج لحفظه هنا.</p>
            <a href="{{ route('products.index') }}" wire:navigate
               class="mt-4 inline-block rounded-md bg-brand-600 px-5 py-2.5 font-extrabold text-white hover:bg-brand-700">تصفّح المنتجات</a>
        </div>
    @else
        <ul class="mt-6 grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-4">
            @foreach ($this->products as $product)
                <li wire:key="fav-{{ $product->id }}" data-fav="{{ $product->id }}"
                    x-data="{ on: true }"
                    @favorites-changed.window="on = window.Favorites.has({{ $product->id }})"
                    x-show="on">
                    <x-store.product-card :product="$product" :currency="$currency" />
                </li>
            @endforeach
        </ul>

        <p x-show="empty" x-cloak class="mt-8 text-center text-ink-soft" role="status">أزلت كل المنتجات من المفضلة.</p>
    @endif
</div>