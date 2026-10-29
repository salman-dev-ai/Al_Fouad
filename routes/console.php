<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');



// حذف طلبات التنبيه المنتهية يوميًا (تقليل البيانات الشخصية المحفوظة).
// يتطلب تشغيل المجدول على الخادم (سطر cron واحد، وسأضيفه لقائمة الإطلاق).
// محليًا لا حاجة له، ويمكن تجربته يدويًا بـ model:prune.
Schedule::command('model:prune')->daily();


