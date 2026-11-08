<?php

namespace App\Support;

use App\Models\Setting;

// إعدادات صور التقييمات بقيم محصورة في حدود آمنة مهما كان المخزَّن.
// التفعيل والعدد والحجم تضبطها الإدارة، والصور تبقى اختيارية دائمًا.
// كل التحققات في الواجهة والإجراءات تقرأ من هنا فقط.
final class ReviewSettings
{
    public const DEFAULT_MAX_PHOTOS = 3;
    public const DEFAULT_MAX_MB = 2;

    // هل رفع الصور مفعّل؟ الافتراضي نعم.
    public static function photosEnabled(): bool
    {
        return (string) Setting::get('reviews.photos_enabled', '1') === '1';
    }

    // الحد الأقصى لعدد الصور (بين 1 و5).
    public static function maxPhotos(): int
    {
        return max(1, min(5, (int) Setting::get('reviews.max_photos', self::DEFAULT_MAX_PHOTOS)));
    }

    // الحد الأقصى لحجم الصورة بالميغابايت (بين 1 و5).
    public static function maxPhotoMb(): int
    {
        return max(1, min(5, (int) Setting::get('reviews.max_photo_mb', self::DEFAULT_MAX_MB)));
    }

    // يحفظ الإعدادات الثلاثة دفعة واحدة من صفحة الإدارة.
    public static function save(bool $enabled, int $maxPhotos, int $maxMb): void
    {
        Setting::put('reviews.photos_enabled', $enabled ? '1' : '0');
        Setting::put('reviews.max_photos', max(1, min(5, $maxPhotos)));
        Setting::put('reviews.max_photo_mb', max(1, min(5, $maxMb)));
    }
}
