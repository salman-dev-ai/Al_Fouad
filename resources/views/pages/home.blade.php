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

    {{-- ========== الهيرو: نص وتصنيفات + صورة مقوّسة متحركة ========== --}}
    <section
        class="relative isolate overflow-hidden"
        aria-roledescription="شريط عروض متحرك"
        aria-label="العروض"
        x-data="{
            i: 0,
            n: {{ max(1, $count) }},
            p: 0,
            hover: false,
            paused: false,
            x0: 0,
            timer: null,
            init() {
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { this.paused = true; }
                if (this.n > 1) {
                    this.timer = setInterval(() => {
                        if (this.hover || this.paused) return;
                        this.p += 1;
                        if (this.p >= 100) this.next();
                    }, 60);
                }
            },
            destroy() { clearInterval(this.timer); },
            go(k) { this.i = (k + this.n) % this.n; this.p = 0; },
            next() { this.go(this.i + 1); },
            prev() { this.go(this.i - 1); },
        }"
        @mouseenter="hover = true" @mouseleave="hover = false"
        @focusin="hover = true" @focusout="hover = false"
        @touchstart.passive="x0 = $event.changedTouches[0].clientX"
        @touchend="const d = $event.changedTouches[0].clientX - x0; if (Math.abs(d) > 50) { d > 0 ? prev() : next(); }"
        @keydown.left.prevent="next()" @keydown.right.prevent="prev()"
    >
        {{-- زخارف خلفية --}}
        <div class="absolute -end-24 top-8 -z-10 size-72 rounded-full bg-[#efe7f3] sm:size-96" aria-hidden="true"></div>
        <x-lucide-heart class="absolute end-[48%] top-14 -z-10 hidden size-16 -rotate-12 text-[#fbd9cf] lg:block" aria-hidden="true" />

        <div class="mx-auto grid max-w-7xl items-center gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[1.05fr_1fr] lg:gap-10 lg:px-8 lg:py-12">

            {{-- ===== العمود النصي (يمين في RTL) ===== --}}
            <div class="order-2 lg:order-1">
                @if ($count > 0)
                    <div class="grid" :aria-live="paused ? 'polite' : 'off'">
                        @foreach ($ads as $ad)
                            <div class="col-start-1 row-start-1 transition-opacity duration-500"
                                 :class="i === {{ $loop->index }} ? 'opacity-100' : 'pointer-events-none opacity-0'"
                                 :inert="i !== {{ $loop->index }}"
                                 :aria-hidden="i !== {{ $loop->index }} ? 'true' : 'false'"
                                 role="group" aria-roledescription="شريحة"
                                 aria-label="{{ $loop->iteration }} من {{ $count }}">
                                <h2 class="text-4xl font-extrabold leading-tight text-brand-700 sm:text-5xl lg:text-6xl">{{ $ad['title'] }}</h2>

                                @if ($ad['description'])
                                    <p class="mt-4 text-lg leading-relaxed text-[#a8946f] sm:text-xl">{{ $ad['description'] }}</p>
                                @endif

                                <a href="{{ $ad['href'] ?? route('products.index') }}"
                                   @if ($ad['href'] === null || $ad['internal']) wire:navigate @else rel="noopener noreferrer" @endif
                                   class="mt-7 inline-flex items-center gap-3 rounded-full bg-brand-600 px-8 py-3.5 text-lg font-extrabold text-white shadow-sm transition hover:bg-brand-700">
                                    تسوّق الآن
                                    <x-lucide-chevron-left class="size-5" aria-hidden="true" />
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <h2 class="text-4xl font-extrabold leading-tight text-brand-700 sm:text-5xl lg:text-6xl">أهلًا بك في {{ config('app.name') }}</h2>
                    <p class="mt-4 text-lg text-[#a8946f] sm:text-xl">اختر مقاسك ولونك بسهولة، وادفع عند الاستلام.</p>
                    <a href="{{ route('products.index') }}" wire:navigate
                       class="mt-7 inline-flex items-center gap-3 rounded-full bg-brand-600 px-8 py-3.5 text-lg font-extrabold text-white shadow-sm transition hover:bg-brand-700">
                        تسوّق الآن
                        <x-lucide-chevron-left class="size-5" aria-hidden="true" />
                    </a>
                @endif

                {{-- التصنيفات تحت النص --}}
                @if ($heroCats->isNotEmpty())
                    <div class="mt-10">
                        <h2 class="text-xl font-extrabold">تسوّق حسب الفئة</h2>

                        <ul class="mt-4 grid grid-cols-3 gap-3 sm:gap-4">
                            @foreach ($heroCats as $category)
                                <li>
                                    <a href="{{ route('products.index', ['category' => $category->slug]) }}" wire:navigate
                                       class="group block overflow-hidden rounded-3xl {{ $pastels[$loop->index % 3] }}">
                                        <div class="aspect-3/4 overflow-hidden">
                                            @if ($category->image)
                                                <img src="{{ asset('storage/' . $category->image) }}" alt="" loading="lazy"
                                                     class="size-full object-cover transition duration-500 group-hover:scale-105">
                                            @else
                                                <div class="grid size-full place-items-center text-brand-600">
                                                    <x-lucide-shirt class="size-12" aria-hidden="true" />
                                                </div>
                                            @endif
                                        </div>
                                        <div class="m-1.5 flex items-center justify-between gap-1 rounded-2xl bg-white/70 px-3 py-2.5 sm:m-2">
                                            <span class="truncate text-xs font-extrabold sm:text-sm">{{ $category->name }}</span>
                                            <x-lucide-chevron-left class="size-4 shrink-0 text-ink-soft" aria-hidden="true" />
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        @if ($moreCats->isNotEmpty())
                            <ul class="mt-4 flex flex-wrap gap-2" aria-label="تصنيفات أخرى">
                                @foreach ($moreCats as $category)
                                    <li>
                                        <a href="{{ route('products.index', ['category' => $category->slug]) }}" wire:navigate
                                           class="inline-block rounded-full border border-line bg-white px-4 py-1.5 text-sm font-semibold hover:bg-surface">{{ $category->name }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </div>

            {{-- ===== الصورة المقوّسة (يسار في RTL) ===== --}}
            <div class="relative order-1 lg:order-2">
                <div class="relative aspect-4/3 overflow-hidden rounded-t-[40%_30%] rounded-b-[2.5rem] bg-brand-50 shadow-sm sm:aspect-16/10 lg:aspect-auto lg:min-h-136">
                    @forelse ($ads as $ad)
                        @if ($ad['image'])
                            <img src="{{ $ad['image'] }}" alt="{{ $ad['title'] }}"
                                 @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif
                                 class="absolute inset-0 size-full object-cover transition-opacity duration-700"
                                 :class="i === {{ $loop->index }} ? 'opacity-100' : 'opacity-0'">
                        @else
                            <div class="absolute inset-0 grid place-items-center bg-linear-to-bl from-brand-50 to-brand-100 text-brand-600 transition-opacity duration-700"
                                 :class="i === {{ $loop->index }} ? 'opacity-100' : 'opacity-0'" aria-hidden="true">
                                <x-lucide-shirt class="size-32 opacity-40" />
                            </div>
                        @endif
                    @empty
                        <div class="absolute inset-0 grid place-items-center bg-linear-to-bl from-brand-50 to-brand-100 text-brand-600" aria-hidden="true">
                            <x-lucide-shirt class="size-32 opacity-40" />
                        </div>
                    @endforelse

                    @if ($count > 1)
                        {{-- السهمان على جانبي الصورة: اليسار = التالي في الاتجاه العربي --}}
                        <button type="button" @click="next()" aria-label="الشريحة التالية"
                                class="absolute left-3 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/95 text-ink shadow hover:bg-white">
                            <x-lucide-chevron-left class="size-5" aria-hidden="true" />
                        </button>
                        <button type="button" @click="prev()" aria-label="الشريحة السابقة"
                                class="absolute right-3 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/95 text-ink shadow hover:bg-white">
                            <x-lucide-chevron-right class="size-5" aria-hidden="true" />
                        </button>
                    @endif
                </div>

                @if ($count > 1)
                    {{-- النقاط + شريط التقدم + إيقاف --}}
                    <div class="mt-4 flex items-center gap-4 px-2">
                        <div class="flex items-center gap-2">
                            @foreach ($ads as $ad)
                                <button type="button" @click="go({{ $loop->index }})"
                                        aria-label="الانتقال إلى الشريحة {{ $loop->iteration }}"
                                        :aria-current="i === {{ $loop->index }} ? 'true' : 'false'"
                                        :class="i === {{ $loop->index }} ? 'bg-brand-600' : 'bg-brand-600/25 hover:bg-brand-600/50'"
                                        class="size-3 rounded-full transition"></button>
                            @endforeach
                        </div>

                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-black/10" aria-hidden="true">
                            <div class="h-full rounded-full bg-brand-600" :style="`width: ${p}%`"></div>
                        </div>

                        <button type="button" @click="paused = !paused"
                                :aria-label="paused ? 'تشغيل التبديل التلقائي' : 'إيقاف التبديل التلقائي'"
                                class="grid size-9 place-items-center rounded-full bg-ink/70 text-white hover:bg-ink">
                            <x-lucide-pause class="size-4" x-show="!paused" aria-hidden="true" />
                            <x-lucide-play class="size-4" x-show="paused" x-cloak aria-hidden="true" />
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ========== الأكثر مبيعًا (أو المميزة إن لم توجد مبيعات) ========== --}}
    @if ($topProducts->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8" aria-labelledby="top-heading">
            <div class="flex items-end justify-between gap-4">
                <h2 id="top-heading" class="text-2xl font-extrabold">{{ $topTitle }}</h2>
                <a href="{{ route('products.index') }}" wire:navigate
                   class="text-sm font-bold text-brand-700 underline underline-offset-4">عرض الكل</a>
            </div>

            <ul class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4 xl:grid-cols-6">
                @foreach ($topProducts as $product)
                    <li><x-store.product-card :product="$product" :currency="$currency" /></li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- ========== المميزة (تظهر فقط حين يكون القسم الأول مبيعات حقيقية) ========== --}}
    @if ($bestReal && $featured->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8" aria-labelledby="featured-heading">
            <div class="flex items-end justify-between gap-4">
                <h2 id="featured-heading" class="text-2xl font-extrabold">منتجات مميزة</h2>
                <a href="{{ route('products.index') }}" wire:navigate
                   class="text-sm font-bold text-brand-700 underline underline-offset-4">عرض الكل</a>
            </div>

            <ul class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4 xl:grid-cols-6">
                @foreach ($featured as $product)
                    <li><x-store.product-card :product="$product" :currency="$currency" /></li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- ========== أحدث المنتجات ========== --}}
    @if ($newest->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8" aria-labelledby="newest-heading">
            <div class="flex items-end justify-between gap-4">
                <h2 id="newest-heading" class="text-2xl font-extrabold">وصل حديثًا</h2>
                <a href="{{ route('products.index', ['sort' => 'newest']) }}" wire:navigate
                   class="text-sm font-bold text-brand-700 underline underline-offset-4">عرض الكل</a>
            </div>

            <ul class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4">
                @foreach ($newest as $product)
                    <li><x-store.product-card :product="$product" :currency="$currency" /></li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($topProducts->isEmpty() && $newest->isEmpty())
        <p class="mx-auto max-w-7xl px-4 py-16 text-center text-ink-soft">سنضيف المنتجات هنا قريبًا.</p>
    @endif

    {{-- ========== شريط المزايا ========== --}}
    <section aria-label="مزايا التسوق" class="border-y border-line bg-white">
        <ul class="mx-auto grid max-w-7xl grid-cols-2 gap-4 px-4 py-6 sm:px-6 lg:grid-cols-4 lg:px-8">
            @foreach ([
                ['truck', 'توصيل إلى بابك', 'نوصل طلبك أينما كنت'],
                ['banknote', 'الدفع عند الاستلام', 'ادفع بعد أن تستلم طلبك'],
                ['message-circle', 'تواصل عبر واتساب', 'نرد على استفساراتك مباشرة'],
                ['palette', 'مقاسات وألوان متعددة', 'اختر ما يناسبك بسهولة'],
            ] as [$icon, $title, $text])
                <li class="flex items-center gap-3">
                    <span class="grid size-11 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-700">
                        <x-dynamic-component :component="'lucide-' . $icon" class="size-5" aria-hidden="true" />
                    </span>
                    <span>
                        <span class="block text-sm font-extrabold">{{ $title }}</span>
                        <span class="block text-xs text-ink-soft">{{ $text }}</span>
                    </span>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- ========== دعوة للتواصل ========== --}}
    @if ($whatsappUrl)
        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8" aria-labelledby="help-heading">
            <div class="flex flex-col items-center justify-between gap-5 rounded-3xl bg-brand-700 p-8 text-center text-white sm:flex-row sm:text-start">
                <div>
                    <h2 id="help-heading" class="text-xl font-extrabold sm:text-2xl">تحتاج مساعدة في اختيار المقاس؟</h2>
                    <p class="mt-1 text-white/90">راسلنا على واتساب وسنساعدك في اختيار الأنسب.</p>
                </div>
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                   class="inline-flex shrink-0 items-center gap-2 rounded-full bg-white px-6 py-3 font-extrabold text-brand-700 hover:bg-brand-50">
                    <x-lucide-message-circle class="size-5" aria-hidden="true" />
                    تواصل معنا
                </a>
            </div>
        </section>
    @endif
</div>
