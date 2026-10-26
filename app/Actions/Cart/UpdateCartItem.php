<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateCartItem
{
    /**
     * @throws ValidationException
     */
    public function execute(Cart $cart, int $itemId, int $quantity): CartItem
    {
        if ($quantity < 1 || $quantity > AddItemToCart::MAX_QUANTITY) {
            throw ValidationException::withMessages([
                'quantity' => 'الكمية يجب أن تكون بين 1 و' . AddItemToCart::MAX_QUANTITY . '.',
            ]);
        }

        return DB::transaction(function () use ($cart, $itemId, $quantity): CartItem {
            Cart::query()->whereKey($cart->getKey())->lockForUpdate()->firstOrFail();

            // نبحث داخل سلة الزائر فقط: لا يمكن تعديل عنصر من سلة غيره (منع IDOR)
            $item = CartItem::query()
                ->where('cart_id', $cart->id)
                ->whereKey($itemId)
                ->with('productVariant.product.category')
                ->first();

            if (! $item) {
                throw ValidationException::withMessages(['item' => 'هذا العنصر غير موجود في سلتك.']);
            }

            $variant = $item->productVariant;

            if (! $variant->product->is_active || ! $variant->product->category->is_active) {
                throw ValidationException::withMessages(['item' => 'لم يعد هذا المنتج متاحًا، احذفه من السلة.']);
            }

            // الزيادة تُقيَّد بالمخزون، أما التقليل فمسموح دائمًا
            if ($quantity > $item->quantity && $quantity > $variant->stock_quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "المتاح من هذا الخيار {$variant->stock_quantity} فقط.",
                ]);
            }

            $item->update(['quantity' => $quantity]);
            $cart->touch();

            return $item;
        });
    }
}
