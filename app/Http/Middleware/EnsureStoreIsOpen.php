<?php

namespace App\Http\Middleware;

use App\Models\StoreSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStoreIsOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        // لوحة الإدارة تبقى مفتوحة دائمًا حتى تستطيع إيقاف الصيانة
        if ($request->is('admin', 'admin/*')) {
            return $next($request);
        }

        if (! StoreSetting::current()?->maintenance_mode) {
            return $next($request);
        }

        // المدير المسجّل يتصفح المتجر عادي ليتأكد من عمله قبل الفتح
        if ($request->user()?->is_admin) {
            return $next($request);
        }

        // طلبات Livewire الصادرة من صفحات الإدارة (مثل نموذج تسجيل الدخول)
        if ($request->hasHeader('X-Livewire')) {
            $referer = (string) parse_url((string) $request->headers->get('referer'), PHP_URL_PATH);

            if ($referer === '/admin' || str_starts_with($referer, '/admin/')) {
                return $next($request);
            }
        }

        abort(503, '', ['Retry-After' => '3600']);
    }
}