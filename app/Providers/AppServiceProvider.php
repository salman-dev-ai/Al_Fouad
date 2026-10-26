<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Actions\Cart\ResolveCart;
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
        //
    }
}
