<?php

use App\Enums\OrderStatus;
use App\Models\Advertisement;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('الفؤاد — أزياء عصرية')] class extends Component {
    // عملة المتجر من إعدادات الإدارة.
    #[Computed]
    public function currency(): string
    {
        return StoreSetting::current()?->currency_code ?? 'YER';
    }

    // شرائح الهيرو من الإعلانات السارية فقط. الروابط تُفلتر: داخلي (/...) أو https فقط.
    #[Computed]
    public function ads(): array
    {
        return Advertisement::query()->current()->limit(6)->get()->map(function (Advertisement $ad): array {
            $link = trim((string) $ad->link_url);

            $internal = str_starts_with($link, '/') && ! str_starts_with($link, '//') && ! str_contains($link, '\\');
            $external = str_starts_with($link, 'https://');

            return [
                'title' => $ad->title,
                'description' => $ad->description,
                'image' => $ad->image_path ? asset('storage/' . $ad->image_path) : null,
                'href' => ($internal || $external) ? $link : null,
                'internal' => $internal,
            ];
        })->all();
    }

    // التصنيفات النشطة التي فيها منتجات.
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()
            ->active()
            ->withCount(['products' => fn ($q) => $q->active()])
            ->orderBy('name')
            ->get()
            ->filter(fn (Category $c) => $c->products_count > 0)
            ->values();
    }

    // معرّفات الأكثر مبيعًا من الطلبات غير الملغاة (مجموع الكميات)، أو فارغة إن لم توجد مبيعات.
    #[Computed]
    public function bestSellerIds(): array
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->whereNotNull('order_items.product_id')
            ->groupBy('order_items.product_id')
            ->orderByDesc(DB::raw('SUM(order_items.quantity)'))
            ->limit(12)
            ->pluck('order_items.product_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    // الأكثر مبيعًا (6) مرتبة حسب المبيعات، ومقيَّدة بالمنتجات النشطة المعروضة.
    #[Computed]
    public function bestSellers(): Collection
    {
        $ids = $this->bestSellerIds;

        if ($ids === []) {
            return new Collection();
        }

        return $this->productsQuery()
            ->whereIn('products.id', $ids)
            ->get()
            ->sortBy(fn (Product $p): int => (int) array_search($p->id, $ids, true))
            ->take(6)
            ->values();
    }

    #[Computed]
    public function featured(): Collection
    {
        return $this->productsQuery()->featured()->latest('id')->limit(6)->get();
    }

    #[Computed]
    public function newest(): Collection
    {
        return $this->productsQuery()->latest('id')->limit(8)->get();
    }

    #[Computed]
    public function whatsappUrl(): ?string
    {
        $phone = PhoneNumber::normalize(
            StoreSetting::current()?->whatsapp_phone,
            config('store.default_country_code')
        );

        return $phone
            ? 'https://wa.me/' . PhoneNumber::digits($phone) . '?text=' . rawurlencode('مرحبًا، أحتاج مساعدة في اختيار المقاس.')
            : null;
    }

    private function productsQuery()
    {
        return Product::query()
            ->active()
            ->whereHas('category', fn ($q) => $q->active())
            ->whereHas('variants')
            ->with(['category:id,name', 'images', 'variants', 'colors:id,product_id,sort_order'])
            ->withMin('variants', 'price');
    }
};
?>

@php
    $ads = $this->ads;
    $count = count($ads);
    $currency = $this->currency;
    $categories = $this->categories;
    $featured = $this->featured;
    $newest = $this->newest;
    $whatsappUrl = $this->whatsappUrl;

    // الأكثر مبيعًا الحقيقي، وإن لم يوجد نعرض المميزة بعنوانها الصادق.
    $bestReal = $this->bestSellers->isNotEmpty();
    $topProducts = $bestReal ? $this->bestSellers : $featured;
    $topTitle = $bestReal ? 'الأكثر مبيعًا' : 'منتجات مميزة';

    $heroCats = $categories->take(3);
    $moreCats = $categories->slice(3);
    $pastels = ['bg-[#efe7f3]', 'bg-[#e4f0e4]', 'bg-[#fde6de]'];
@endphp

<x-slot:description>تسوّق أحدث الأزياء من الفؤاد: مقاسات وألوان متعددة، والدفع عند الاستلام.</x-slot:description>

<div class="bg-[#fbf9f4]">
    <h1 class="sr-only">الفؤاد — متجر أزياء</h1>

    @include('components.home.hero')

    @include('components.home.top-products')

    @include('components.home.featured')

    @include('components.home.newest')

    @if ($topProducts->isEmpty() && $newest->isEmpty())
        <p class="mx-auto max-w-7xl px-4 py-16 text-center text-ink-soft">سنضيف المنتجات هنا قريبًا.</p>
    @endif

    @include('components.home.features-bar')

    @include('components.home.whatsapp-cta')
</div>
