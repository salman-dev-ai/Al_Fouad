<?php

namespace App\Models;

use App\Models\Concerns\FlushesAboutCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

// المحتوى المفرد لصفحة «قصتنا» (سجل واحد): النصوص والقوائم التي يديرها المدير.
// الأقسام الظاهرة تُحدَّد بقائمة بيضاء (SECTIONS)، وأي قسم فارغ لا يُعرض أصلًا.
// الكاش يُمسح تلقائيًا عند الحفظ بفضل الخاصية FlushesAboutCache.
#[Fillable([
    'is_published', 'visible_sections', 'hero_title', 'hero_subtitle', 'hero_image',
    'story_title', 'story_body', 'story_image', 'mission', 'vision', 'stats', 'core_values',
    'payment_methods', 'shipping_info', 'return_days', 'return_policy', 'faqs', 'seo_description',
])]

class AboutPage extends Model
{
    use FlushesAboutCache;

    public const CACHE_KEY = 'about:page:v1';

        public const FOOTER_CACHE_KEY = 'about:footer:v1';



        protected function casts(): array{

        return[
    'is_published' => 'boolean',
    'visible_sections' => 'array',
    'stats' => 'array',
    'core_values' => 'array',
    'payment_methods' => 'array',
    'faqs' => 'array',
    'return_days' => 'integer',
        ];
        }



    public const SECTIONS = [
        'story' => 'قصتنا ورسالتنا ورؤيتنا',
        'stats' => 'شريط الأرقام',
        'values' => 'قيمنا ومميزاتنا',
        'partners' => 'شركاؤنا (شعارات دائرية)',
        'testimonials' => 'آراء عملائنا',
        'branches' => 'فروعنا',
        'payments' => 'طرق الدفع والتوصيل',
        'returns' => 'سياسة الاسترجاع',
        'faqs' => 'الأسئلة الشائعة',
        'contact' => 'معلومات التواصل',
    ];

    // السجل الوحيد للصفحة أو null إن لم يُنشأ بعد.
    // نفس أسلوب StoreSetting::current، ويستعمله مدير الصفحة وواجهة العرض.
    // أول حفظ من الإدارة ينشئه.
    public static function current(): ?self
    {
        return static::query()->first();
    }

    protected function aboutImageColumns(): array
    {
        return ['hero_image', 'story_image'];
    }
}
