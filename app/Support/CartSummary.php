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
    ) {}

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
