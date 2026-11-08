<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Models\StoreSetting;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
 #[Title('المنتجات')]
 class extends Component {
    use WithPagination;

    private const PER_PAGE = 12;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: 'newest')]
    public string $sort = 'newest';

    #[Url(as: 'min', except: '')]
    public string $minPrice = '';

    #[Url(as: 'max', except: '')]
    public string $maxPrice = '';

    #[Url(as: 'stock', except: false)]
    public bool $inStockOnly = false;

    #[Url(as: 'size', except: '')]
    public string $size = '';

    #[Url(as: 'color', except: '')]
    public string $color = '';

    // أي تغيير في الفلاتر يعيدنا للصفحة الأولى (بدون المساس بترقيم الصفحات نفسه).
    public function updated(string $name): void
    {
        if (! str_starts_with($name, 'paginators')) {
            $this->resetPage();
        }
    }

    // يختار تصنيفًا بالـ slug؛ يُقبل فقط إن كان ضمن التصنيفات المعروضة (قائمة بيضاء من الكاش، بلا استعلام).
    public function selectCategory(string $slug): void
    {
        $valid = array_column($this->facets['categories'], 'slug');
        $this->category = in_array($slug, $valid, true) ? $slug : '';
        $this->resetPage();
    }

    // يصفّر كل الفلاتر والبحث والفرز.
    public function clearFilters(): void
    {
        $this->reset(['search', 'category', 'sort', 'minPrice', 'maxPrice', 'inStockOnly', 'size', 'color']);
        $this->resetPage();
    }

    #[Computed]
    public function sortOptions(): array
    {
        return [
            'newest' => 'الأحدث',
            'price_asc' => 'السعر: من الأقل',
            'price_desc' => 'السعر: من الأعلى',
            'name' => 'الاسم',
        ];
    }

    #[Computed]
    public function currency(): string
    {
        return StoreSetting::current()?->currency_code ?? 'YER';
    }

    // كل بيانات الفلاتر (تصنيفات، ألوان، مقاسات، حدود السعر) في مفتاح كاش واحد لمدة 10 دقائق.
    // مفتاح واحد = استعلام واحد على كاش قاعدة البيانات بدل أربعة. تتجدد تلقائيًا بعد انتهاء المدة.
    // لتجديدها فورًا بعد تعديل في الإدارة: php artisan cache:clear.
    #[Computed]
    public function facets(): array
    {
        return Cache::remember('catalog:facets:v1', 600, function (): array {
            $activeIds = Product::query()->active()->select('id');

            $categories = Category::query()
                ->active()
                ->withCount(['products as products_count' => fn ($q) => $q->where('is_active', true)])
                ->orderBy('name')
                ->get()
                ->filter(fn (Category $c): bool => $c->products_count > 0)
                ->map(fn (Category $c): array => [
                    'slug' => (string) $c->slug,
                    'name' => (string) $c->name,
                    'image' => $c->image,
                ])
                ->values()
                ->all();

            $colors = ProductColor::query()
                ->whereIn('product_id', $activeIds)
                ->groupBy('name')
                ->orderBy('name')
                ->limit(24)
                ->selectRaw('name, MIN(hex) as hex')
                ->get()
                ->map(fn ($c): array => ['name' => (string) $c->name, 'hex' => $c->hex])
                ->all();

            $order = array_values((array) ProductVariant::SIZES);
            $rank = fn ($s): int => ($i = array_search($s, $order, true)) === false ? 99 : $i;
            $sizes = ProductVariant::query()->whereIn('product_id', $activeIds)->distinct()->pluck('size')->all();
            usort($sizes, fn ($a, $b): int => $rank($a) <=> $rank($b));

            $range = ProductVariant::query()
                ->whereIn('product_id', $activeIds)
                ->selectRaw('MIN(price) as lo, MAX(price) as hi')
                ->first();

            return [
                'categories' => $categories,
                'colors' => $colors,
                'sizes' => array_values($sizes),
                'price' => ['min' => (int) floor((float) ($range?->lo ?? 0)), 'max' => (int) ceil((float) ($range?->hi ?? 0))],
            ];
        });
    }

    #[Computed]
    public function hasFilters(): bool
    {
        return $this->search !== '' || $this->category !== '' || $this->minPrice !== '' || $this->maxPrice !== ''
            || $this->inStockOnly || $this->sort !== 'newest' || $this->size !== '' || $this->color !== '';
    }

    #[Computed]
    public function products()
    {
        $facets = $this->facets;

        $term = mb_substr(trim($this->search), 0, 100);
        $min = is_numeric($this->minPrice) && (float) $this->minPrice >= 0 ? (float) $this->minPrice : null;
        $max = is_numeric($this->maxPrice) && (float) $this->maxPrice >= 0 ? (float) $this->maxPrice : null;

        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        // قوائم بيضاء: أي قيمة من الرابط خارج القوائم تُتجاهل.
        $size = in_array($this->size, $facets['sizes'], true) ? $this->size : '';
        $color = in_array($this->color, array_column($facets['colors'], 'name'), true) ? $this->color : '';
        $category = in_array($this->category, array_column($facets['categories'], 'slug'), true) ? $this->category : '';

        $query = Product::query()
            ->active()
            ->whereHas('category', fn ($q) => $q->where('is_active', true)->when($category !== '', fn ($q) => $q->where('slug', $category)))
            ->when($term !== '', function ($q) use ($term) {
                // نُلغي رموز LIKE الخاصة حتى لا يتحول % أو _ إلى حرف بدل.
                $like = '%' . addcslashes($term, '%_\\') . '%';
                $q->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('description', 'like', $like));
            })
            ->when($min !== null || $max !== null || $this->inStockOnly || $size !== '' || $color !== '',
                fn ($q) => $q->whereHas('variants', fn ($v) => $v
                    ->when($min !== null, fn ($v) => $v->where('price', '>=', $min))
                    ->when($max !== null, fn ($v) => $v->where('price', '<=', $max))
                    ->when($this->inStockOnly, fn ($v) => $v->where('stock_quantity', '>', 0))
                    ->when($size !== '', fn ($v) => $v->where('size', $size))
                    ->when($color !== '', fn ($v) => $v->where('color', $color))))
            ->with(['category:id,name', 'images', 'variants', 'colors:id,product_id,sort_order'])
            ->withMin('variants', 'price');

        match ($this->sort) {
            'price_asc' => $query->orderBy('variants_min_price')->orderByDesc('id'),
            'price_desc' => $query->orderByDesc('variants_min_price')->orderByDesc('id'),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        return $query->paginate(self::PER_PAGE);
    }
};
?>

@php
    $facets = $this->facets;
    $pb = $facets['price'];
    $hasRange = $pb['max'] > $pb['min'];
    $span = $pb['max'] - $pb['min'];
    $step = $span >= 5000 ? 100 : ($span >= 500 ? 10 : 1);
    $loVal = is_numeric($minPrice) ? max($pb['min'], min((int) $minPrice, $pb['max'])) : $pb['min'];
    $hiVal = is_numeric($maxPrice) ? max($pb['min'], min((int) $maxPrice, $pb['max'])) : $pb['max'];
    $priceActive = $minPrice !== '' || $maxPrice !== '';
    $btn = 'inline-flex items-center gap-2 rounded-xl border border-line bg-white px-4 py-2.5 text-sm font-semibold transition hover:bg-surface focus-visible:outline-2 focus-visible:outline-brand-500';
@endphp

<div class="bg-linear-to-b from-brand-50/50 via-white to-white">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8"
         x-data="{
            open: { price: true, color: true, size: true },
            init() { if (window.innerWidth < 1024) this.open = { price: false, color: false, size: false } },
         }">

        <x-store.breadcrumbs :items="[['label' => 'الرئيسية', 'url' => route('home')], ['label' => 'المنتجات']]" />

        <h1 class="mt-4 text-3xl font-extrabold sm:text-5xl">المنتجات</h1>

        {{-- ========== شريط التصنيفات ========== --}}
        @if (count($facets['categories']) > 0)
            <nav class="mt-6 flex flex-col gap-4 rounded-2xl bg-brand-50/70 p-4 lg:flex-row lg:items-center" aria-label="تصفح حسب التصنيف">
                <h2 class="shrink-0 text-base font-extrabold lg:px-2">تصفح حسب التصنيف</h2>

                <ul class="flex gap-3 overflow-x-auto pb-1 [scrollbar-width:none] lg:flex-wrap lg:overflow-visible lg:pb-0">
                    <li class="shrink-0">
                        <button type="button" wire:click="selectCategory('')" aria-pressed="{{ $category === '' ? 'true' : 'false' }}"
                                @class([
                                    'flex items-center gap-3 rounded-xl border p-1.5 pe-5 text-sm font-bold transition',
                                    'border-brand-600 bg-brand-600 text-white shadow-sm' => $category === '',
                                    'border-line bg-white hover:bg-surface' => $category !== '',
                                ])>
                            <span class="grid size-10 place-items-center rounded-lg bg-white/20">
                                <x-lucide-layout-grid class="size-5" aria-hidden="true" />
                            </span>
                            الكل
                        </button>
                    </li>

                    @foreach ($facets['categories'] as $item)
                        <li class="shrink-0" wire:key="cat-{{ $item['slug'] }}">
                            <button type="button" wire:click="$set('category', @js($item['slug']))"
                                    aria-pressed="{{ $category === $item['slug'] ? 'true' : 'false' }}"
                                    @class([
                                        'flex items-center gap-3 rounded-xl border p-1.5 pe-5 text-sm font-bold transition',
                                        'border-brand-600 bg-brand-600 text-white shadow-sm' => $category === $item['slug'],
                                        'border-line bg-white hover:bg-surface' => $category !== $item['slug'],
                                    ])>
                                @if ($item['image'])
                                    <img src="{{ asset('storage/' . $item['image']) }}" alt="" width="40" height="40" loading="lazy" decoding="async"
                                         class="size-10 rounded-lg object-cover">
                                @else
                                    <span class="grid size-10 place-items-center rounded-lg bg-brand-50 text-brand-600">
                                        <x-lucide-shirt class="size-5" aria-hidden="true" />
                                    </span>
                                @endif
                                {{ $item['name'] }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif

        {{-- ========== صف الأدوات ========== --}}
        <div class="mt-5 flex flex-wrap items-center gap-3 rounded-2xl border border-line bg-white p-3 shadow-sm sm:p-4">
            <div class="relative min-w-full flex-1 sm:min-w-64">
                <label for="search" class="sr-only">ابحث عن منتج</label>
                <x-lucide-search class="pointer-events-none absolute inset-s-3 top-1/2 size-5 -translate-y-1/2 text-ink-soft" aria-hidden="true" />
                <input id="search" type="search" wire:model.live.debounce.300ms="search" maxlength="100" autocomplete="off"
                       placeholder="ابحث عن منتج..."
                       class="w-full rounded-xl border border-line bg-white py-2.5 ps-10 pe-3 text-sm focus:border-brand-500">
            </div>

            <p class="px-1 text-sm text-ink-soft" aria-live="polite">
                <strong class="text-base text-ink">{{ $this->products->total() }}</strong> نتيجة
            </p>

            <div class="relative">
                <label for="sort" class="sr-only">ترتيب النتائج</label>
                <x-lucide-arrow-up-down class="pointer-events-none absolute inset-s-3 top-1/2 size-4 -translate-y-1/2" aria-hidden="true" />
                <select id="sort" wire:model.live="sort"
                        class="appearance-none rounded-xl border border-line bg-white py-2.5 ps-9 pe-9 text-sm font-semibold focus:border-brand-500">
                    @foreach ($this->sortOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <x-lucide-chevron-down class="pointer-events-none absolute inset-e-3 top-1/2 size-4 -translate-y-1/2" aria-hidden="true" />
            </div>

            @if ($hasRange)
                <button type="button" @click="open.price = !open.price" :aria-expanded="open.price" aria-controls="panel-price" class="{{ $btn }}">
                    <x-lucide-coins class="size-4" aria-hidden="true" /> السعر
                    @if ($priceActive) <span class="size-2 rounded-full bg-brand-600" aria-label="مفعّل"></span> @endif
                    <x-lucide-chevron-down class="size-4 transition" ::class="open.price && 'rotate-180'" aria-hidden="true" />
                </button>
            @endif

            @if (count($facets['colors']) > 0)
                <button type="button" @click="open.color = !open.color" :aria-expanded="open.color" aria-controls="panel-color" class="{{ $btn }}">
                    <x-lucide-palette class="size-4" aria-hidden="true" /> اللون
                    @if ($color !== '') <span class="size-2 rounded-full bg-brand-600" aria-label="مفعّل"></span> @endif
                    <x-lucide-chevron-down class="size-4 transition" ::class="open.color && 'rotate-180'" aria-hidden="true" />
                </button>
            @endif

            @if (count($facets['sizes']) > 0)
                <button type="button" @click="open.size = !open.size" :aria-expanded="open.size" aria-controls="panel-size" class="{{ $btn }}">
                    <x-lucide-shirt class="size-4" aria-hidden="true" /> المقاس
                    @if ($size !== '') <span class="size-2 rounded-full bg-brand-600" aria-label="مفعّل"></span> @endif
                    <x-lucide-chevron-down class="size-4 transition" ::class="open.size && 'rotate-180'" aria-hidden="true" />
                </button>
            @endif

            <button type="button" wire:click="$toggle('inStockOnly')" aria-pressed="{{ $inStockOnly ? 'true' : 'false' }}"
                    @class([$btn, 'border-brand-600! bg-brand-50! text-brand-700' => $inStockOnly])>
                <x-lucide-package-check class="size-4" aria-hidden="true" /> المتوفر فقط
            </button>

            @if ($this->hasFilters)
                <button type="button" wire:click="clearFilters"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-50 px-4 py-2.5 text-sm font-bold text-brand-700 hover:bg-brand-100">
                    مسح الفلاتر
                    <x-lucide-rotate-ccw class="size-4" aria-hidden="true" />
                </button>
            @endif
        </div>

        {{-- ========== لوحة الفلاتر ========== --}}
        <div class="mt-4 grid gap-6 rounded-2xl border border-line bg-white p-5 shadow-sm md:grid-cols-[1.4fr_1fr_1fr]"
             x-show="open.price || open.color || open.size" x-transition.opacity>

            {{-- السعر: شريط مزدوج (لا طلبات أثناء السحب، فقط عند الإفلات) + حقول رقمية --}}
            @if ($hasRange)
                <section id="panel-price" x-show="open.price" aria-labelledby="price-h"
                         x-data="{
                            min: {{ $pb['min'] }}, max: {{ $pb['max'] }}, step: {{ $step }},
                            lo: {{ $loVal }}, hi: {{ $hiVal }},
                            init() {
                                this.$wire.$watch('minPrice', v => this.lo = v === '' ? this.min : Math.max(this.min, Number(v)));
                                this.$wire.$watch('maxPrice', v => this.hi = v === '' ? this.max : Math.min(this.max, Number(v)));
                            },
                            pct(v) { return ((v - this.min) / (this.max - this.min)) * 100 },
                            fixLo() { if (this.lo > this.hi) this.lo = this.hi },
                            fixHi() { if (this.hi < this.lo) this.hi = this.lo },
                            commit() {
                                this.$wire.set('minPrice', this.lo <= this.min ? '' : String(this.lo), false);
                                this.$wire.set('maxPrice', this.hi >= this.max ? '' : String(this.hi));
                            },
                         }">
                    <h2 id="price-h" class="text-base font-extrabold">السعر ({{ $this->currency }})</h2>

                    <div class="relative mt-5 h-6" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
                        <div class="absolute inset-x-0 top-1/2 h-1 -translate-y-1/2 rounded-full bg-line" aria-hidden="true"></div>
                        <div class="absolute top-1/2 h-1 -translate-y-1/2 rounded-full bg-brand-600" aria-hidden="true"
                             :style="`inset-inline-start:${pct(lo)}%; inset-inline-end:${100 - pct(hi)}%`"></div>

                        <input type="range" class="range-dual absolute inset-0 w-full" :min="min" :max="max" :step="step"
                               x-model.number="lo" @input="fixLo()" @change="commit()" aria-label="أقل سعر"
                               :class="lo > max - step * 2 ? 'z-20' : 'z-10'">
                        <input type="range" class="range-dual absolute inset-0 w-full z-10" :min="min" :max="max" :step="step"
                               x-model.number="hi" @input="fixHi()" @change="commit()" aria-label="أعلى سعر">
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <label class="flex flex-1 items-center gap-2 text-sm text-ink-soft">
                            من
                            <input type="number" min="0" inputmode="numeric" placeholder="{{ $pb['min'] }}" wire:model.live.debounce.600ms="minPrice"
                                   class="w-full rounded-lg border border-line px-3 py-2 text-sm text-ink focus:border-brand-500">
                        </label>
                        <span aria-hidden="true">–</span>
                        <label class="flex flex-1 items-center gap-2 text-sm text-ink-soft">
                            إلى
                            <input type="number" min="0" inputmode="numeric" placeholder="{{ $pb['max'] }}" wire:model.live.debounce.600ms="maxPrice"
                                   class="w-full rounded-lg border border-line px-3 py-2 text-sm text-ink focus:border-brand-500">
                        </label>
                    </div>
                </section>
            @endif

            {{-- اللون --}}
            @if (count($facets['colors']) > 0)
                <fieldset id="panel-color" x-show="open.color" class="md:border-s md:border-line md:ps-6">
                    <legend class="mb-4 text-base font-extrabold">اللون</legend>
                    <div class="flex flex-wrap gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" wire:model.live="color" value="" class="peer sr-only">
                            <span class="block rounded-full border border-line px-3 py-1.5 text-xs font-semibold peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600">الكل</span>
                        </label>

                        @foreach ($facets['colors'] as $option)
                            <label class="cursor-pointer" title="{{ $option['name'] }}" wire:key="color-{{ $loop->index }}">
                                <input type="radio" wire:model.live="color" value="{{ $option['name'] }}" class="peer sr-only">
                                @if ($option['hex'])
                                    <span class="block size-9 rounded-full border border-line ring-offset-2 peer-checked:ring-2 peer-checked:ring-brand-600 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600"
                                          style="background-color: {{ $option['hex'] }}"></span>
                                    <span class="sr-only">{{ $option['name'] }}</span>
                                @else
                                    <span class="block rounded-full border border-line px-3 py-1.5 text-xs font-semibold peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600">{{ $option['name'] }}</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endif

            {{-- المقاس --}}
            @if (count($facets['sizes']) > 0)
                <fieldset id="panel-size" x-show="open.size" class="md:border-s md:border-line md:ps-6">
                    <legend class="mb-4 text-base font-extrabold">المقاس</legend>
                    <div class="flex flex-wrap gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" wire:model.live="size" value="" class="peer sr-only">
                            <span class="block min-w-11 rounded-lg border border-line px-3 py-2 text-center text-sm font-semibold peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600">الكل</span>
                        </label>
                        @foreach ($facets['sizes'] as $option)
                            <label class="cursor-pointer" wire:key="size-{{ $loop->index }}">
                                <input type="radio" wire:model.live="size" value="{{ $option }}" class="peer sr-only">
                                <span class="block min-w-11 rounded-lg border border-line px-3 py-2 text-center text-sm font-semibold peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600">{{ $option }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endif
        </div>

        {{-- ========== النتائج ========== --}}
        <section class="mt-8" aria-label="نتائج المنتجات">
            <p wire:loading class="sr-only" role="status">جارٍ تحديث النتائج…</p>

            @if ($this->products->isEmpty())
                <div class="rounded-2xl border border-dashed border-line bg-surface px-6 py-16 text-center">
                    <x-lucide-search-x class="mx-auto size-10 text-ink-soft" aria-hidden="true" />
                    <h2 class="mt-4 text-lg font-extrabold">لا توجد منتجات مطابقة</h2>
                    <p class="mt-1 text-sm text-ink-soft">جرّب تغيير كلمات البحث أو تخفيف الفلاتر.</p>
                    <button type="button" wire:click="clearFilters"
                            class="mt-5 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-700">
                        مسح الفلاتر
                    </button>
                </div>
            @else
                <div wire:loading.class="opacity-60"
                     class="grid grid-cols-2 gap-x-4 gap-y-8 transition-opacity sm:gap-x-6 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($this->products as $product)
                        <x-store.product-card :product="$product" :currency="$this->currency" wire:key="product-{{ $product->id }}" />
                    @endforeach
                </div>

                <div class="mt-10">
                    {{ $this->products->onEachSide(0)->links('vendor.pagination.simple-tailwind') }}
                </div>
            @endif
        </section>
    </div>
</div>
