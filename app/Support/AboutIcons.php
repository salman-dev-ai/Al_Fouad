<?php

namespace App\Support;

// قائمة بيضاء بالأيقونات التي يختارها المدير للمميزات وطرق الدفع (مفتاح Lucide => اسم عربي).
// نمنع إدخال اسم أيقونة حر لأن الاسم يتحول إلى مكوّن في الصفحة، واسم غير موجود يكسرها.
// تُستعمل في الإدارة للاختيار، وفي الواجهة للتحقق قبل العرض.
final class AboutIcons
{
    public const OPTIONS = [
        'truck' => 'توصيل',
        'shield-check' => 'ضمان وأمان',
        'badge-check' => 'جودة مضمونة',
        'heart' => 'عناية',
        'sparkles' => 'تميّز',
        'gem' => 'فخامة',
        'package' => 'تغليف وشحن',
        'rotate-ccw' => 'استرجاع',
        'headset' => 'دعم العملاء',
        'wallet' => 'محفظة',
        'banknote' => 'دفع نقدي',
        'credit-card' => 'بطاقة',
        'store' => 'متجر',
        'scissors' => 'تفصيل وخياطة',
        'star' => 'تقييم',
        'users' => 'عملاء',
        'award' => 'جوائز',
        'leaf' => 'خامات طبيعية',
        'clock' => 'سرعة',
        'gift' => 'هدايا',
    ];

    // هل الاسم ضمن القائمة المسموحة؟
    // تستعمله الواجهة قبل رسم الأيقونة، فاسم قديم أو مُتلاعَب به يُتجاهل بدل أن يكسر الصفحة.
    public static function isAllowed(?string $key): bool
    {
        return $key !== null && array_key_exists($key, self::OPTIONS);
    }
}
