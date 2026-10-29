<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController
{
    // ينهي دخول العميل ويبطل الجلسة ويجدد رمز الحماية (CSRF).
    // يعيده إلى الصفحة الرئيسية بعد الخروج.
    // متحكم مستقل وليس دالة مجهولة حتى يعمل تخزين المسارات (route:cache) في الإنتاج.
    public function __invoke(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}