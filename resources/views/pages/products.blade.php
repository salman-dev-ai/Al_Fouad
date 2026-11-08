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

        <x-ui.breadcrumbs :items="[['label' => 'الرئيسية', 'url' => route('home')], ['label' => 'المنتجات']]" />

        <h1 class="mt-4 text-3xl font-extrabold sm:text-5xl">المنتجات</h1>

        @include('components.catalog.category-bar')

        @include('components.catalog.filters-toolbar')

        @include('components.catalog.filters-panel')

        @include('components.catalog.results')
    </div>
</div>
