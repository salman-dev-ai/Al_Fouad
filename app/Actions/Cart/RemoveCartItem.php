<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\CartItem;

class RemoveCartItem
{
    public function execute(Cart $cart, int $itemId): void
    {
        // الحذف مقيّد بسلة الزائر نفسه
        CartItem::query()
            ->where('cart_id', $cart->id)
            ->whereKey($itemId)
            ->delete();

        $cart->touch();
    }
}
