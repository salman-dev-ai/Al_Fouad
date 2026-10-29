<?php

use App\Models\Advertisement;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('الفؤاد — أزياء عصرية')] class extends Component {
    #[Computed]
    public function currency(): string
    {
        return StoreSetting::current()?->currency_code ?? 'YER';
    }

    /** إعلانات فترتها سارية. الروابط تُفلتر هنا: داخلي (/...) أو https فقط. */
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

    #[Computed]
    public function featured(): Collection
    {
        return $this->productsQuery()->featured()->latest('id')->limit(8)->get();
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
@endphp

<x-slot:description>تسوّق أحدث الأزياء من الفؤاد: مقاسات وألوان متعددة، والدفع عند الاستلام.</x-slot:description>

<div>
    <h1 class="sr-only">الفؤاد — متجر أزياء</h1>

    {{-- ========== البانر المتحرك ========== --}}
    @if ($count > 0)
        <section
            aria-roledescription="شريط عروض متحرك"
            aria-label="العروض"
            class="relative"
            x-data="{
                i: 0,
                n: {{ $count }},
                hover: false,
                userPaused: false,
                x0: 0,
                timer: null,
                init() {
                    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    if (reduce) { this.userPaused = true; }
                    if (this.n > 1) {
                        this.timer = setInterval(() => { if (!this.hover && !this.userPaused) this.next(); }, 6000);
                    }
                },
                destroy() { clearInterval(this.timer); },
                next() { this.i = (this.i + 1) % this.n; },
                prev() { this.i = (this.i - 1 + this.n) % this.n; },
            }"
            @mouseenter="hover = true" @mouseleave="hover = false"
            @focusin="hover = true" @focusout="hover = false"
            @touchstart.passive="x0 = $event.changedTouches[0].clientX"
            @touchend="const d = $event.changedTouches[0].clientX - x0; if (Math.abs(d) > 50) { d > 0 ? prev() : next(); }"
            @keydown.left.prevent="next()" @keydown.right.prevent="prev()"
        >
            <div :aria-live="userPaused ? 'polite' : 'off'">
                @foreach ($ads as $ad)
                    <article
                        x-show="i === {{ $loop->index }}"
                        @unless ($loop->first) x-cloak @endunless
                        x-transition:enter="transition-opacity duration-700"
                        x-transition:enter-start="opacity-50"
                        x-transition:enter-end="opacity-100"
                        role="group" aria-roledescription="شريحة"
                        aria-label="{{ $loop->iteration }} من {{ $count }}"
                        class="relative isolate flex min-h-88 items-center overflow-hidden bg-brand-700 sm:min-h-112"
                    >
                        @if ($ad['image'])
                            <img src="{{ $ad['image'] }}" alt="" class="absolute inset-0 -z-20 size-full object-cover"
                                 @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                            <div class="absolute inset-0 -z-10 bg-linear-to-l from-ink/85 via-ink/55 to-ink/10" aria-hidden="true"></div>
                        @else
                            <div class="absolute inset-0 -z-10 bg-linear-to-bl from-brand-600 to-brand-700" aria-hidden="true"></div>
                            <x-lucide-shirt class="absolute -bottom-10 -start-10 -z-10 size-72 text-white/10" aria-hidden="true" />
                        @endif

                        <div class="mx-auto w-full max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                            <div class="max-w-xl text-white">
                                <h2 class="text-3xl font-extrabold leading-tight sm:text-5xl">{{ $ad['title'] }}</h2>

                                @if ($ad['description'])
                                    <p class="mt-4 text-base leading-relaxed text-white/90 sm:text-lg">{{ $ad['description'] }}</p>
                                @endif

                                @if ($ad['href'])
                                    <a href="{{ $ad['href'] }}"
                                       @if ($ad['internal']) wire:navigate @else rel="noopener noreferrer" @endif
                                       class="mt-7 inline-flex items-center gap-2 rounded-md bg-white px-6 py-3 text-base font-extrabold text-brand-700 shadow hover:bg-brand-50">
                                        اكتشف الآن
                                        <x-lucide-arrow-left class="size-5" aria-hidden="true" />
                                    </a>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($count > 1)
                <button type="button" @click="prev()" aria-label="الشريحة السابقة"
                        class="absolute end-auto start-auto right-3 top-1/2 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-ink shadow hover:bg-white max-sm:hidden">
                    <x-lucide-chevron-right class="size-5" aria-hidden="true" />
                </button>
                <button type="button" @click="next()" aria-label="الشريحة التالية"
                        class="absolute left-3 top-1/2 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-ink shadow hover:bg-white max-sm:hidden">
                    <x-lucide-chevron-left class="size-5" aria-hidden="true" />
                </button>

                <div class="absolute inset-x-0 bottom-4 flex items-center justify-center gap-3">
                    <button type="button" @click="userPaused = !userPaused"
                            :aria-label="userPaused ? 'تشغيل التبديل التلقائي' : 'إيقاف التبديل التلقائي'"
                            class="grid size-8 place-items-center rounded-full bg-white/90 text-ink hover:bg-white">
                        <x-lucide-pause class="size-4" x-show="!userPaused" aria-hidden="true" />
                        <x-lucide-play class="size-4" x-show="userPaused" x-cloak aria-hidden="true" />
                    </button>

                    <div class="flex items-center gap-2">
                        @foreach ($ads as $ad)
                            <button type="button" @click="i = {{ $loop->index }}"
                                    aria-label="الانتقال إلى الشريحة {{ $loop->iteration }}"
                                    :aria-current="i === {{ $loop->index }} ? 'true' : 'false'"
                                    :class="i === {{ $loop->index }} ? 'w-8 bg-white' : 'w-2.5 bg-white/60 hover:bg-white/90'"
                                    class="h-2.5 rounded-full transition-all"></button>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    @else
        {{-- لا إعلانات مفعّلة: واجهة ترحيب بسيطة بدل فراغ --}}
        <section class="relative isolate overflow-hidden bg-brand-700">
            <div class="absolute inset-0 -z-10 bg-linear-to-bl from-brand-600 to-brand-700" aria-hidden="true"></div>
            <x-lucide-shirt class="absolute -bottom-10 -start-10 -z-10 size-72 text-white/10" aria-hidden="true" />
            <div class="mx-auto flex min-h-88 max-w-7xl flex-col justify-center px-4 py-12 text-white sm:min-h-112 sm:px-6 lg:px-8">
                <h2 class="max-w-xl text-3xl font-extrabold leading-tight sm:text-5xl">أناقة تليق بك، من الفؤاد</h2>
                <p class="mt-4 max-w-lg text-lg text-white/90">اختر مقاسك ولونك بسهولة، وادفع عند الاستلام.</p>
                <a href="{{ route('products.index') }}" wire:navigate
                   class="mt-7 inline-flex w-fit items-center gap-2 rounded-md bg-white px-6 py-3 text-base font-extrabold text-brand-700 shadow hover:bg-brand-50">
                    تسوّق الآن
                    <x-lucide-arrow-left class="size-5" aria-hidden="true" />
                </a>
            </div>
        </section>
    @endif

    {{-- ========== شريط الثقة ========== --}}
    <section aria-label="مزايا التسوق" class="border-b border-line bg-white">
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

    {{-- ========== التصنيفات ========== --}}
    @if ($categories->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8" aria-labelledby="cats-heading">
            <div class="flex items-end justify-between gap-4">
                <h2 id="cats-heading" class="text-2xl font-extrabold">تسوّق حسب التصنيف</h2>
            </div>

            <ul class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($categories as $category)
                    <li>
                        <a href="{{ route('products.index', ['category' => $category->slug]) }}" wire:navigate
                           class="group relative block aspect-4/3 overflow-hidden rounded-lg bg-brand-50">
                            @if ($category->image)
                                <img src="{{ asset('storage/' . $category->image) }}" alt="" loading="lazy"
                                     class="size-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <div class="grid size-full place-items-center bg-linear-to-bl from-brand-50 to-brand-100 text-brand-600">
                                    <x-lucide-shirt class="size-12" aria-hidden="true" />
                                </div>
                            @endif
                            <div class="absolute inset-0 bg-linear-to-t from-ink/80 via-ink/10 to-transparent" aria-hidden="true"></div>
                            <div class="absolute inset-x-0 bottom-0 p-4 text-white">
                                <span class="block text-lg font-extrabold">{{ $category->name }}</span>
                                <span class="text-xs text-white/85">{{ $category->products_count }} منتج</span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- ========== المنتجات المميزة ========== --}}
    @if ($featured->isNotEmpty())
        <section class="bg-surface py-12" aria-labelledby="featured-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-end justify-between gap-4">
                    <h2 id="featured-heading" class="text-2xl font-extrabold">منتجات مميزة</h2>
                    <a href="{{ route('products.index') }}" wire:navigate
                       class="text-sm font-bold text-brand-700 underline underline-offset-4">عرض الكل</a>
                </div>

                <ul class="mt-6 grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-4">
                    @foreach ($featured as $product)
                        <li><x-store.product-card :product="$product" :currency="$currency" /></li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ========== أحدث المنتجات ========== --}}
    @if ($newest->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8" aria-labelledby="newest-heading">
            <div class="flex items-end justify-between gap-4">
                <h2 id="newest-heading" class="text-2xl font-extrabold">وصل حديثًا</h2>
                <a href="{{ route('products.index', ['sort' => 'newest']) }}" wire:navigate
                   class="text-sm font-bold text-brand-700 underline underline-offset-4">عرض الكل</a>
            </div>

            <ul class="mt-6 grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-4">
                @foreach ($newest as $product)
                    <li><x-store.product-card :product="$product" :currency="$currency" /></li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($featured->isEmpty() && $newest->isEmpty())
        <p class="mx-auto max-w-7xl px-4 py-16 text-center text-ink-soft">سنضيف المنتجات هنا قريبًا.</p>
    @endif

    {{-- ========== دعوة للتواصل ========== --}}
    @if ($whatsappUrl)
        <section class="mx-auto max-w-7xl px-4 pb-14 sm:px-6 lg:px-8" aria-labelledby="help-heading">
            <div class="flex flex-col items-center justify-between gap-5 rounded-xl bg-brand-700 p-8 text-center text-white sm:flex-row sm:text-start">
                <div>
                    <h2 id="help-heading" class="text-xl font-extrabold sm:text-2xl">تحتاج مساعدة في اختيار المقاس؟</h2>
                    <p class="mt-1 text-white/90">راسلنا على واتساب وسنساعدك في اختيار الأنسب.</p>
                </div>
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                   class="inline-flex shrink-0 items-center gap-2 rounded-md bg-white px-6 py-3 font-extrabold text-brand-700 hover:bg-brand-50">
                    <x-lucide-message-circle class="size-5" aria-hidden="true" />
                    تواصل معنا
                </a>
            </div>
        </section>
    @endif
</div>
