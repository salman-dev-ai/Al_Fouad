<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProductVariant;
use App\Models\ProductColor;
use Illuminate\Support\Facades\Cache;

new #[Title('المنتجات')]
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

    /** أي تغيير في الفلاتر يعيدنا للصفحة الأولى. */
    public function updated(string $name): void
    {
        if (!str_starts_with($name, 'paginators')) {
            $this->resetPage();
        }
    }

    public function selectCategory(int $id): void
    {
        $this->category = $id > 0 ? (string) Category::query()->active()->whereKey($id)->value('slug') : '';

        $this->resetPage();
    }

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

    #[Computed]
    public function categories()
    {
        return Category::query()
            ->active()
            ->withCount(['products as products_count' => fn($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get()
            ->filter(fn(Category $category): bool => $category->products_count > 0)
            ->values();
    }


    // ألوان المنتجات الفعّالة كقائمة اسم + درجة (hex)، مجمّعة بالاسم، لعرضها كحلقات ألوان في الفلتر.
    // تُخزَّن 10 دقائق (بيانات عامة بسيطة لا تخص زائرًا بعينه) لأن الفلتر يُرسم في كل طلب.
    // تُحدَّث تلقائيًا بعد انتهاء المدة عند إضافة لون جديد من الإدارة.
    #[Computed]
    public function colorOptions(): array
    {
        return Cache::remember('catalog:filter:colors', 600, fn(): array => ProductColor::query()
            ->whereIn('product_id', Product::query()->active()->select('id'))
            ->groupBy('name')
            ->orderBy('name')
            ->limit(24)
            ->selectRaw('name, MIN(hex) as hex')
            ->get()
            ->map(fn($c): array => ['name' => (string) $c->name, 'hex' => $c->hex])
            ->all());
    }

    // المقاسات الموجودة فعلًا في المنتجات الفعّالة، مرتبة بترتيب ProductVariant::SIZES المعتمد.
    // تُخزَّن 10 دقائق أيضًا، وتُستعمل قائمة بيضاء لقيمة المقاس القادمة من الرابط.
    // أي مقاس خارج القائمة يُتجاهل بدل أن يُنتج نتائج فارغة.
    #[Computed]
    public function sizeOptions(): array
    {
        return Cache::remember('catalog:filter:sizes', 600, function (): array {
            $present = ProductVariant::query()
                ->whereIn('product_id', Product::query()->active()->select('id'))
                ->distinct()
                ->pluck('size')
                ->all();

            $order = array_values((array) ProductVariant::SIZES);

            usort($present, fn($a, $b) => (array_search($a, $order, true) === false ? 99 : array_search($a, $order, true))
                <=> (array_search($b, $order, true) === false ? 99 : array_search($b, $order, true)));

            return array_values($present);
        });
    }

    #[Computed]
    public function hasFilters(): bool
    {
        return $this->search !== '' || $this->category !== '' || $this->minPrice !== '' || $this->maxPrice !== '' || $this->inStockOnly || $this->sort !== 'newest'|| $this->size !== '' || $this->color !== '';
    }

    #[Computed]
    public function products()
    {
        $term = mb_substr(trim($this->search), 0, 100);
        $min = is_numeric($this->minPrice) && $this->minPrice >= 0 ? $this->minPrice : null;
        $max = is_numeric($this->maxPrice) && $this->maxPrice >= 0 ? $this->maxPrice : null;
        // المقاس من قائمة بيضاء، واللون نص قصير يُمرَّر كمعامل (لا SQL خام) فلا خطر منه.
        $size = in_array($this->size, $this->sizeOptions, true) ? $this->size : '';
        $color = mb_substr(trim($this->color), 0, 50);
        $query = Product::query()
            ->active()
            ->whereHas('category', fn($q) => $q->where('is_active', true)->when($this->category !== '', fn($q) => $q->where('slug', $this->category)))
            ->when($term !== '', function ($q) use ($term) {
                // نُلغي رموز البحث الخاصة بـ LIKE حتى لا يتحول % أو _ في النص إلى حرف بدل
                $like = '%' . addcslashes($term, '%_\\') . '%';
                $q->where(fn($q) => $q->where('name', 'like', $like)->orWhere('description', 'like', $like));
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

        // قائمة بيضاء للفرز: لا نثق بالقيمة القادمة من الرابط
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

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8" x-data="{ filtersOpen: false }">
    <x-store.breadcrumbs :items="[['label' => 'الرئيسية', 'url' => route('home')], ['label' => 'المنتجات']]" />

    <div class="mt-4 flex flex-wrap items-end justify-between gap-3">
        <h1 class="text-2xl font-extrabold sm:text-3xl">المنتجات</h1>
        <p class="text-sm text-ink-soft" aria-live="polite">النتائج: {{ $this->products->total() }}</p>
    </div>

    {{-- البحث والفرز --}}
    <div class="mt-6 flex flex-col gap-3 sm:flex-row">
        <div class="relative flex-1">
            <label for="search" class="sr-only">ابحث عن منتج</label>
            <x-lucide-search
                class="pointer-events-none absolute inset-s-3 top-1/2 size-5 -translate-y-1/2 text-ink-soft"
                aria-hidden="true" />
            <input id="search" type="search" wire:model.live.debounce.300ms="search" maxlength="100" autocomplete="off"
                placeholder="ابحث عن منتج..."
                class="w-full rounded-md border border-line bg-white py-2.5 ps-10 pe-3 text-sm focus:border-brand-500">
        </div>

        <div class="flex gap-3">
            <label for="sort" class="sr-only">ترتيب النتائج</label>
            <select id="sort" wire:model.live="sort"
                class="flex-1 rounded-md border border-line bg-white px-3 py-2.5 text-sm focus:border-brand-500 sm:flex-none">
                @foreach ($this->sortOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            <button type="button" @click="filtersOpen = !filtersOpen" :aria-expanded="filtersOpen"
                aria-controls="filters"
                class="flex items-center gap-2 rounded-md border border-line px-3 py-2.5 text-sm font-semibold lg:hidden">
                <x-lucide-sliders-horizontal class="size-4" aria-hidden="true" />
                تصفية
            </button>
        </div>
    </div>

    <div class="mt-6 grid gap-8 lg:grid-cols-[16rem_1fr]">
        {{-- الفلاتر: ثابتة على الشاشات الكبيرة، وتُفتح بزر على الجوال --}}
        <aside id="filters" class="space-y-6 max-lg:hidden" :class="{ 'max-lg:block!': filtersOpen }">
            <section aria-labelledby="cat-heading">
                <h2 id="cat-heading" class="mb-3 text-sm font-extrabold">التصنيف</h2>
                <ul class="space-y-1">
                    <li>
                        <button type="button" wire:click="selectCategory(0)"
                            aria-pressed="{{ $category === '' ? 'true' : 'false' }}" @class([
                                'flex w-full items-center justify-between rounded-md px-3 py-2 text-start text-sm hover:bg-brand-50',
                                'bg-brand-50 font-bold text-brand-700' => $category === '',
                            ])>
                            الكل
                        </button>
                    </li>
                    @foreach ($this->categories as $item)
                        <li wire:key="cat-{{ $item->id }}">
                            <button type="button" wire:click="selectCategory({{ $item->id }})"
                                aria-pressed="{{ $category === $item->slug ? 'true' : 'false' }}" @class([
                                    'flex w-full items-center justify-between rounded-md px-3 py-2 text-start text-sm hover:bg-brand-50',
                                    'bg-brand-50 font-bold text-brand-700' => $category === $item->slug,
                                ])>
                                <span>{{ $item->name }}</span>
                                <span class="text-xs text-ink-soft">{{ $item->products_count }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section aria-labelledby="price-heading">
                <h2 id="price-heading" class="mb-3 text-sm font-extrabold">السعر ({{ $this->currency }})</h2>
                <div class="flex items-center gap-2">
                    <label for="min" class="sr-only">أقل سعر</label>
                    <input id="min" type="number" min="0" inputmode="numeric" placeholder="من"
                        wire:model.live.debounce.500ms="minPrice"
                        class="w-full rounded-md border border-line px-3 py-2 text-sm focus:border-brand-500">
                    <span aria-hidden="true">–</span>
                    <label for="max" class="sr-only">أعلى سعر</label>
                    <input id="max" type="number" min="0" inputmode="numeric" placeholder="إلى"
                        wire:model.live.debounce.500ms="maxPrice"
                        class="w-full rounded-md border border-line px-3 py-2 text-sm focus:border-brand-500">
                </div>
            </section>

                        {{-- فلتر اللون: حلقات ألوان حقيقية، أزرار راديو مخفية بصريًا (تعمل بالكيبورد وقارئ الشاشة). --}}
            @if (count($this->colorOptions) > 0)
                <fieldset>
                    <legend class="mb-3 text-sm font-extrabold">اللون</legend>
                    <div class="flex flex-wrap gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" wire:model.live="color" value="" class="peer sr-only">
                            <span class="block rounded-full border border-line px-3 py-1.5 text-xs font-semibold peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600">الكل</span>
                        </label>

                        @foreach ($this->colorOptions as $option)
                            <label class="cursor-pointer" title="{{ $option['name'] }}" wire:key="color-{{ $loop->index }}">
                                <input type="radio" wire:model.live="color" value="{{ $option['name'] }}" class="peer sr-only">
                                @if ($option['hex'])
                                    <span class="block size-8 rounded-full border border-line ring-offset-2 peer-checked:ring-2 peer-checked:ring-brand-600 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600"
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

            {{-- فلتر المقاس: نفس نمط الراديو، والمقاسات من الخيارات الموجودة فعلًا. --}}
            @if (count($this->sizeOptions) > 0)
                <fieldset>
                    <legend class="mb-3 text-sm font-extrabold">المقاس</legend>
                    <div class="flex flex-wrap gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" wire:model.live="size" value="" class="peer sr-only">
                            <span class="block rounded-md border border-line px-3 py-1.5 text-xs font-semibold peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600">الكل</span>
                        </label>
                        @foreach ($this->sizeOptions as $option)
                            <label class="cursor-pointer" wire:key="size-{{ $loop->index }}">
                                <input type="radio" wire:model.live="size" value="{{ $option }}" class="peer sr-only">
                                <span class="block min-w-10 rounded-md border border-line px-3 py-1.5 text-center text-xs font-semibold peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600">{{ $option }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endif
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model.live="inStockOnly" class="size-4 accent-brand-600">
                المتوفر فقط
            </label>

            @if ($this->hasFilters)
                <button type="button" wire:click="clearFilters"
                    class="text-sm font-semibold text-brand-700 underline underline-offset-4">
                    مسح كل الفلاتر
                </button>
            @endif
        </aside>

        {{-- النتائج --}}
        <section aria-label="نتائج المنتجات">
            <p wire:loading class="sr-only" role="status">جارٍ تحديث النتائج…</p>

            @if ($this->products->isEmpty())
                <div class="rounded-lg border border-dashed border-line bg-surface px-6 py-16 text-center">
                    <x-lucide-search-x class="mx-auto size-10 text-ink-soft" aria-hidden="true" />
                    <h2 class="mt-4 text-lg font-extrabold">لا توجد منتجات مطابقة</h2>
                    <p class="mt-1 text-sm text-ink-soft">جرّب تغيير كلمات البحث أو تخفيف الفلاتر.</p>
                    <button type="button" wire:click="clearFilters"
                        class="mt-5 rounded-md bg-brand-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-700">
                        مسح الفلاتر
                    </button>
                </div>
            @else
                <div wire:loading.class="opacity-60"
                    class="grid grid-cols-2 gap-x-4 gap-y-8 transition-opacity sm:gap-x-6 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($this->products as $product)
                        <x-store.product-card :product="$product" :currency="$this->currency"
                            wire:key="product-{{ $product->id }}" />
                    @endforeach
                </div>

                <div class="mt-10">
                    {{ $this->products->onEachSide(0)->links('vendor.pagination.simple-tailwind') }}
                </div>
            @endif
        </section>
    </div>
</div>
