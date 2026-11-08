<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Actions\Cart\ResolveCart;
use App\Enums\OrderStatus;
use App\Models\Order;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
                // نسخة واحدة لكل طلب، فلا نكرر استعلام السلة بين مكوّنات الصفحة
        $this->app->scoped(ResolveCart::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // يسجّل وقت التسليم مرة واحدة عند أول انتقال الطلب إلى «تم التسليم» (أساس نافذة التقييم).
        // يعمل مع أي مسار يحفظ الطلب عبر الموديل، فلا نعدّل ChangeOrderStatus.
        Order::updating(function (Order $order): void {
            if ($order->isDirty('status')
                && $order->delivered_at === null
                && ($order->getAttributes()['status'] ?? null) === OrderStatus::Delivered->value) {
                $order->delivered_at = now();
            }
        });    }
}
