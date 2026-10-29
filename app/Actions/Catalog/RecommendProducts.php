<?php

namespace App\Actions\Catalog;

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// يبني اقتراحات المنتجات: «قد يعجبك أيضًا» لمنتج، و«أكمل إطلالتك» لمحتويات السلة.
// يعتمد على المبيعات الفعلية إن وُجدت (الأكثر مبيعًا، والمشترى معًا)، وإلا على المميز والأحدث.
// لا يقترح إلا منتجات متاحة فعلًا، ويخزّن المعرّفات مؤقتًا لا الكائنات.
class RecommendProducts
{
    private const LOOKBACK_DAYS = 90;

    private const MIN_ORDERS_FOR_TOGETHER = 10;

    private const POOL_SIZE = 24;

    // اقتراحات منتج: معرّفاتها تُخزَّن 15 دقيقة، ثم تُجلب المنتجات طازجة مع تحقق التوفر الحالي.
    // يعيد مجموعة فارغة إن لم يكن المنتج موجودًا.
    // الحد الأقصى الافتراضي 8 منتجات.
    public function relatedTo(int $productId, int $limit = 8): Collection
    {
        $product = Product::query()->select(['id', 'category_id'])->find($productId);

        if (! $product) {
            return new Collection();
        }

        $ids = Cache::remember(
            "recs:related:{$product->id}:{$limit}",
            900,
            fn (): array => $this->relatedIds($product, $limit),
        );

        return $this->load($ids, $limit);
    }

    // اقتراحات مكمّلة لمحتويات السلة (لا تتضمن أي منتج فيها)، وتُخزَّن 10 دقائق بحسب محتويات السلة.
    // يقرأ منتجات السلة من قاعدة البيانات لا من المتصفح.
    // سلة فارغة تعني لا اقتراحات.
    public function forCart(Cart $cart, int $limit = 4): Collection
    {
        $productIds = DB::table('cart_items')
            ->join('product_variants', 'product_variants.id', '=', 'cart_items.product_variant_id')
            ->where('cart_items.cart_id', $cart->id)
            ->distinct()
            ->pluck('product_variants.product_id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        if ($productIds === []) {
            return new Collection();
        }

        $ids = Cache::remember(
            'recs:cart:' . md5(implode(',', $productIds)) . ":{$limit}",
            600,
            fn (): array => $this->cartIds($productIds, $limit),
        );

        return $this->load($ids, $limit);
    }

    // معرّفات اقتراحات منتج بالترتيب: نفس التصنيف (الأعلى مبيعًا) ثم المشترى معه ثم مميز من تصنيفات أخرى.
    // كل مرحلة تكمل ما نقص حتى الوصول للحد.
    // لا تتكرر المعرّفات ولا يظهر المنتج نفسه.
    private function relatedIds(Product $product, int $limit): array
    {
        $ids = array_slice($this->rank(
            $this->available()->where('category_id', $product->category_id)->whereKeyNot($product->id)
        ), 0, $limit);

        if (count($ids) < $limit) {
            $together = $this->boughtTogether([$product->id]);
            $valid = $this->available()->whereKeyNot($product->id)->whereNotIn('id', $ids)->whereIn('id', $together)->pluck('id')->all();
            $ids = array_merge($ids, array_values(array_intersect($together, $valid)));
        }

        if (count($ids) < $limit) {
            $ids = array_merge($ids, $this->rank(
                $this->available()->whereKeyNot($product->id)->whereNotIn('id', $ids)
            ));
        }

        return array_slice(array_values(array_unique($ids)), 0, $limit);
    }

    // معرّفات مكمّلات السلة: المشترى مع منتجاتها، ثم منتجات من تصنيفات غير موجودة في السلة، ثم أي متاح.
    // لا تتضمن أبدًا منتجًا موجودًا في السلة.
    // تُرتَّب كل مرحلة بالأعلى مبيعًا ثم المميز.
    private function cartIds(array $productIds, int $limit): array
    {
        $categoryIds = Product::query()->whereIn('id', $productIds)->pluck('category_id')->unique()->all();

        $together = $this->boughtTogether($productIds);
        $valid = $this->available()->whereNotIn('id', $productIds)->whereIn('id', $together)->pluck('id')->all();
        $ids = array_values(array_intersect($together, $valid));

        if (count($ids) < $limit) {
            $ids = array_merge($ids, $this->rank(
                $this->available()->whereNotIn('id', [...$productIds, ...$ids])->whereNotIn('category_id', $categoryIds)
            ));
        }

        if (count($ids) < $limit) {
            $ids = array_merge($ids, $this->rank(
                $this->available()->whereNotIn('id', [...$productIds, ...$ids])
            ));
        }

        return array_slice(array_values(array_unique($ids)), 0, $limit);
    }

    // المنتجات المعروضة فعلًا: مفعّلة، وتصنيفها مفعّل، ولها مقاس واحد على الأقل متوفر في المخزون.
    // هذه هي سياسة المتجر لاستبعاد غير المتاح من الاقتراحات.
    // تُستعمل في كل مراحل الاقتراح وعند الجلب النهائي.
    private function available(): Builder
    {
        return Product::query()
            ->active()
            ->whereHas('category', fn ($q) => $q->active())
            ->whereHas('variants', fn ($q) => $q->where('stock_quantity', '>', 0));
    }

    // يأخذ حتى 24 مرشحًا (المميز ثم الأحدث) ويرتبهم بالأعلى مبيعًا في آخر 90 يومًا، ثم المميز، ثم الأحدث.
    // عند غياب المبيعات يتساوى الجميع بصفر فيبقى ترتيب المميز والأحدث.
    // يعيد معرّفات فقط.
    private function rank(Builder $query): array
    {
        $pool = $query->orderByDesc('is_featured')->orderByDesc('id')->limit(self::POOL_SIZE)->get(['id', 'is_featured']);

        if ($pool->isEmpty()) {
            return [];
        }

        $sold = $this->soldQuantities($pool->pluck('id')->all());

        $ranked = $pool->all();

        usort($ranked, fn ($a, $b) => [$sold[$b->id] ?? 0, (int) $b->is_featured, $b->id]
            <=> [$sold[$a->id] ?? 0, (int) $a->is_featured, $a->id]);

        return array_map(fn ($p) => $p->id, $ranked);
    }

    // الكمية المباعة لكل منتج من القائمة خلال آخر 90 يومًا، في الطلبات المؤكدة وما بعدها فقط (لا الملغاة).
    // استعلام واحد مجمَّع بدل استعلام لكل منتج.
    // يعيد مصفوفة معرّف المنتج ⇒ الكمية.
    private function soldQuantities(array $productIds): array
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('order_items.product_id', $productIds)
            ->whereIn('orders.status', $this->salesStatuses())
            ->where('orders.created_at', '>=', now()->subDays(self::LOOKBACK_DAYS))
            ->groupBy('order_items.product_id')
            ->selectRaw('order_items.product_id as product_id, SUM(order_items.quantity) as sold')
            ->pluck('sold', 'product_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    // المنتجات التي اشتُريت في نفس الطلبات مع المنتجات المعطاة (الأكثر كمية أولًا)، من آخر 300 طلب.
    // لا تُستعمل إلا إن وُجد 10 طلبات على الأقل (بيانات كافية)، وإلا تُعاد قائمة فارغة.
    // الطلبات الملغاة لا تُحتسب.
    private function boughtTogether(array $productIds): array
    {
        $orderIds = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('order_items.product_id', $productIds)
            ->whereIn('orders.status', $this->salesStatuses())
            ->orderByDesc('orders.id')
            ->limit(300)
            ->pluck('orders.id')
            ->unique()
            ->values()
            ->all();

        if (count($orderIds) < self::MIN_ORDERS_FOR_TOGETHER) {
            return [];
        }

        return DB::table('order_items')
            ->whereIn('order_id', $orderIds)
            ->whereNotIn('product_id', $productIds)
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->orderByRaw('SUM(quantity) DESC')
            ->limit(self::POOL_SIZE)
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    // الحالات التي تُعدّ مبيعات فعلية (مطابقة لحساب الإحصاءات في لوحة الإدارة).
    private function salesStatuses(): array
    {
        return [OrderStatus::Confirmed->value, OrderStatus::Shipped->value, OrderStatus::Delivered->value];
    }

    // يجلب المنتجات بمعرّفاتها مع تحقق التوفر الحالي، ويحافظ على ترتيب المعرّفات ويقصّ للحد.
    // يحمّل الصور والخيارات والألوان والسعر الأدنى مسبقًا لتجنب N+1 عند عرض البطاقات.
    // معرّف لم يعد متاحًا يُحذف بصمت.
    private function load(array $ids, int $limit): Collection
    {
        if ($ids === []) {
            return new Collection();
        }

        $products = $this->available()
            ->whereIn('id', $ids)
            ->with(['category:id,name', 'images', 'variants', 'colors:id,product_id,sort_order'])
            ->withMin('variants', 'price')
            ->get()
            ->keyBy('id');

        return new Collection(
            collect($ids)->map(fn ($id) => $products->get($id))->filter()->take($limit)->values()->all()
        );
    }
}
