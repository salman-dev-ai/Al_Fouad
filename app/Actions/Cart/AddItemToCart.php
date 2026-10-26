<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddItemToCart
{
    public const MAX_QUANTITY = 10;

    /**
     * @throws ValidationException
     */
    public function execute(Cart $cart, int $variantId, int $quantity): CartItem
    {
        if ($quantity < 1 || $quantity > self::MAX_QUANTITY) {
            throw ValidationException::withMessages([
                'quantity' => 'الكمية يجب أن تكون بين 1 و' . self::MAX_QUANTITY . '.',
            ]);
        }

        return DB::transaction(function () use ($cart, $variantId, $quantity): CartItem {
            // نقفل صف السلة لتتسلسل الإضافات المتزامنة ولا يتكرر السطر
            Cart::query()->whereKey($cart->getKey())->lockForUpdate()->firstOrFail();

            $variant = ProductVariant::query()->with('product.category')->find($variantId);

            if (! $variant || ! $variant->product->is_active || ! $variant->product->category->is_active) {
                throw ValidationException::withMessages(['variant' => 'هذا الخيار غير متاح حاليًا.']);
            }

            if ($variant->stock_quantity < 1) {
                throw ValidationException::withMessages(['quantity' => 'نفد هذا الخيار من المخزون.']);
            }

            $item = CartItem::query()->firstOrNew([
                'cart_id' => $cart->id,
                'product_variant_id' => $variant->id,
            ]);

            $current = $item->exists ? $item->quantity : 0;
            $limit = min($variant->stock_quantity, self::MAX_QUANTITY);

            if ($current + $quantity > $limit) {
                $remaining = max(0, $limit - $current);

                throw ValidationException::withMessages([
                    'quantity' => $remaining > 0
                        ? "يمكنك إضافة {$remaining} فقط من هذا الخيار."
                        : 'وصلت إلى أقصى كمية متاحة لهذا الخيار.',
                ]);
            }

            $item->quantity = $current + $quantity;
            $item->save();
            $cart->touch();

            return $item;
        });
    }
}
