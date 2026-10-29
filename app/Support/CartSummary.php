<?php

namespace App\Support;

use App\Models\CartItem;
use App\Models\ProductVariant;

/**
 * ملخص السلة بوحدات صغرى (أعداد صحيحة). يُبنى في الخادم فقط.
 */
final readonly class CartSummary
{
    /**
     * @param  array<int, array{item: CartItem, variant: ProductVariant, quantity: int, unit_minor: int, line_minor: int, issue: ?string}>  $lines
     */
    public function __construct(
        public array $lines,
        public int $subtotalMinor,
        public bool $taxEnabled,
        public string $taxRate,
        public int $taxMinor,
        public int $deliveryFeeMinor,
        public int $totalMinor,
        // حد الشحن المجاني بالوحدة الصغرى (null = غير مفعّل أو الشحن أصلًا مجاني).
        public ?int $freeShippingThresholdMinor = null,
        // المبلغ المتبقي للوصول للحد (0 = تحقق الحد).
        public int $freeShippingRemainingMinor = 0,
    ) {}


    // هل تحقق شرط الشحن المجاني في هذه السلة؟
    // يستعملها شريط السلة لعرض رسالة التهنئة بدل المبلغ المتبقي.
    // false إن كانت الميزة غير مفعّلة أو السلة فارغة.
    public function qualifiesForFreeShipping(): bool
    {
        return $this->freeShippingThresholdMinor !== null
            && ! $this->isEmpty()
            && $this->freeShippingRemainingMinor === 0;
    }

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }

    /** هل في السلة عناصر لا تسمح بإتمام الطلب (نفد/مؤرشف/كمية أكبر من المتاح)؟ */
    public function hasBlockingIssues(): bool
    {
        foreach ($this->lines as $line) {
            if ($line['issue'] !== null) {
                return true;
            }
        }

        return false;
    }
}
