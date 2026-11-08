<?php

namespace App\Models\Concerns;

use App\Models\AboutPage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

// خاصية مشتركة لموديلات صفحة «قصتنا»: تمسح كاش الصفحة عند أي حفظ أو حذف، وتحذف الصور القديمة المستبدلة أو المحذوفة.
// تُسجَّل أحداثها تلقائيًا (bootFlushesAboutCache) فلا تحتاج أي استدعاء، وتعمل مع أي مسار حفظ.
// كل موديل يحدد أعمدة صوره بدالة aboutImageColumns، وتُستعمل أيضًا مع StoreSetting لأن بياناته تظهر في الصفحة.
trait FlushesAboutCache
{
    // تسجيل الأحداث: بعد الحفظ امسح الكاش واحذف الصورة القديمة إن استُبدلت، وبعد الحذف احذف الصور وامسح الكاش.
    // الحذف بعد نجاح الحفظ (updated) لا قبله، حتى لا نخسر الملف إن فشل التحديث.
    // الملفات على القرص public، وهو نفس قرص رفع الصور في الإدارة.
    public static function bootFlushesAboutCache(): void
    {
        static::updated(function (Model $model): void {
            foreach ($model->aboutImageColumns() as $column) {
                $old = $model->getOriginal($column);

                if ($model->wasChanged($column) && filled($old) && $old !== $model->{$column}) {
                    Storage::disk('public')->delete($old);
                }
            }
        });

        static::saved(function () : void {
            Cache::forget(AboutPage::FOOTER_CACHE_KEY);
            Cache::forget(AboutPage::CACHE_KEY);
        });

        static::deleted(function (Model $model): void {
            foreach ($model->aboutImageColumns() as $column) {
                if (filled($model->{$column})) {
                    Storage::disk('public')->delete($model->{$column});
                }
            }

            Cache::forget(AboutPage::CACHE_KEY);
Cache::forget(AboutPage::FOOTER_CACHE_KEY);

            });
    }

    // أعمدة الصور في الموديل (الافتراضي: لا شيء)، يعيد تعريفها كل موديل له صور.
    // تُحذف ملفاتها عند الاستبدال أو الحذف.
    // مثال: ['logo'] للشريك.
    protected function aboutImageColumns(): array
    {
        return [];
    }
}
