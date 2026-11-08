<?php

namespace App\Actions\Reviews;

use App\Models\Product;
use App\Models\Review;

// يحسب متوسط التقييمات المعتمدة (من 0 إلى 5) وعددها لمنتج ويخزّنهما في جدول المنتجات.
// التحديث بالاستعلام المباشر (لا تعبئة جماعية) فلا حاجة لجعل الأعمدة fillable.
// يعيد صفرين إن لم يبقَ أي تقييم معتمد.
class RecalculateProductRating
{
    public function execute(int $productId): void
    {
        $stats = Review::query()
            ->approved()
            ->where('product_id', $productId)
            ->selectRaw('COUNT(*) as total, AVG(rating) as average')
            ->first();

        $total = (int) ($stats?->total ?? 0);

        Product::query()->whereKey($productId)->update([
            'rating_count' => $total,
            'rating_avg' => $total > 0 ? round((float) $stats->average, 2) : 0,
        ]);
    }
}
