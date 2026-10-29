<?php

namespace App\Observers;

use App\Models\ProductVariant;
use App\Models\StockAlert;

// يراقب تحديثات الخيارات ليعلّم تنبيهات التوفر حين يعود المخزون من صفر.
// منفصل عن موديل ProductVariant حتى لا نمس منطقه الحالي (تزامن اللون والمنتج).
// يعمل عند الحفظ بالموديل (لوحة الإدارة)، أما التحديث المباشر بالاستعلام فلا يُطلقه.
class ProductVariantObserver
{
    // بعد التحديث: إن انتقل المخزون من أقل من 1 إلى أكبر من 0 علّم تنبيهات هذا الخيار.
    // wasChanged تتأكد أن حقل المخزون تغيّر فعلًا، وgetOriginal القيمة قبل الحفظ.
    // لا يعمل شيئًا في بقية التحديثات (سعر، sku...).
    public function updated(ProductVariant $variant): void
    {
        if ($variant->wasChanged('stock_quantity')
            && (int) $variant->getOriginal('stock_quantity') < 1
            && (int) $variant->stock_quantity > 0) {
            StockAlert::markAvailable($variant->id);
        }
    }
}
