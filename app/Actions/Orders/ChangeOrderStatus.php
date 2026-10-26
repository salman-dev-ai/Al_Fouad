<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use DomainException;
use Illuminate\Support\Facades\DB;

class ChangeOrderStatus
{
    /**
     * @throws DomainException إن كان الانتقال غير مسموح
     */
    public function execute(Order $order, OrderStatus $newStatus): Order
    {
        return DB::transaction(function () use ($order, $newStatus): Order {
            // نقفل صف الطلب ونقرأه من جديد، حتى لا يغيّره مديران في اللحظة نفسها
            $locked = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->status->canTransitionTo($newStatus)) {
                throw new DomainException(
                    "لا يمكن نقل الطلب من «{$locked->status->getLabel()}» إلى «{$newStatus->getLabel()}»."
                );
            }

            if ($newStatus === OrderStatus::Cancelled) {
                $this->restoreStock($locked);
            }

            $locked->update(['status' => $newStatus]);

            return $locked;
        });
    }

    private function restoreStock(Order $order): void
    {
        foreach ($order->items()->whereNotNull('product_variant_id')->get() as $item) {
            // increment ذرّية على مستوى قاعدة البيانات، ولا تعتمد على قيمة قديمة في الذاكرة
            ProductVariant::query()
                ->whereKey($item->product_variant_id)
                ->increment('stock_quantity', $item->quantity);
        }
    }
} 
