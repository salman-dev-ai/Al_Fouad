<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\StoreSetting;
use App\Support\CartSummary;
use App\Support\Money;

class SummarizeCart
{
    public function execute(Cart $cart): CartSummary
    {
        $items = $cart->items()
            ->with(['productVariant.product.category', 'productVariant.product.images'])
            ->orderBy('id')
            ->get();

        $lines = [];
        $subtotal = 0;

        foreach ($items as $item) {
            $variant = $item->productVariant;
            $product = $variant->product;

            $issue = match (true) {
                ! $product->is_active || ! $product->category->is_active => 'لم يعد هذا المنتج متاحًا، احذفه من السلة.',
                $variant->stock_quantity < 1 => 'نفد هذا الخيار من المخزون، احذفه من السلة.',
                $item->quantity > $variant->stock_quantity => "المتاح من هذا الخيار {$variant->stock_quantity} فقط، عدّل الكمية.",
                default => null,
            };

            $unit = Money::toMinor($variant->price);
            $line = $unit * $item->quantity;
            $subtotal += $line;

            $lines[] = [
                'item' => $item,
                'variant' => $variant,
                'quantity' => $item->quantity,
                'unit_minor' => $unit,
                'line_minor' => $line,
                'issue' => $issue,
            ];
        }

        $settings = StoreSetting::current();
        $taxEnabled = (bool) $settings?->tax_enabled;
        $taxRate = $taxEnabled ? (string) $settings->tax_rate : '0.00';

        // نسبة الضريبة بالنقاط الأساسية (5.00% => 500)، والتقريب لأقرب وحدة صغرى بأعداد صحيحة
        $taxMinor = $taxEnabled ? intdiv($subtotal * Money::toMinor($taxRate) + 5000, 10000) : 0;

        // حد الشحن المجاني: يُقارن بمجموع المنتجات قبل الضريبة، وبأعداد صحيحة فقط.
        // إن كانت رسوم التوصيل صفرًا أصلًا أو الحد غير مضبوط نلغي الشريط (threshold = null).
        // إن بلغ المجموع الحد صارت رسوم التوصيل صفرًا، وهذا الحساب نفسه يستعمله PlaceOrder.
        $baseDelivery = $lines === [] ? 0 : Money::toMinor($settings?->delivery_fee ?? 0);
        $rawThreshold = $settings?->free_shipping_threshold;
        $threshold = ($rawThreshold !== null && $baseDelivery > 0 && Money::toMinor($rawThreshold) > 0)
            ? Money::toMinor($rawThreshold)
            : null;
        $remaining = $threshold === null ? 0 : max(0, $threshold - $subtotal);
        $deliveryFee = ($threshold !== null && $remaining === 0) ? 0 : $baseDelivery;
        return new CartSummary(
            lines: $lines,
            subtotalMinor: $subtotal,
            taxEnabled: $taxEnabled,
            taxRate: $taxRate,
            taxMinor: $taxMinor,
            deliveryFeeMinor: $deliveryFee,
            totalMinor: $subtotal + $taxMinor + $deliveryFee,
              freeShippingThresholdMinor: $threshold,
            freeShippingRemainingMinor: $remaining,

        );
    }
}
