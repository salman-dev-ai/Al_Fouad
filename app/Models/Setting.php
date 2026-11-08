<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

// إعداد بسيط مفتاح/قيمة، تُقرأ كل الإعدادات بكاش واحد وتُمسح عند أي كتابة.
// يُستعمل عبر Setting::get وSetting::put، والقيم تُخزَّن نصوصًا.
// لا يُعرَّض لأي مدخل من العميل، فالكتابة فقط من لوحة الإدارة.
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    // يقرأ إعدادًا (أو القيمة الافتراضية إن لم يوجد) من الكاش.
    // استعلام واحد للكل ثم بلا استعلامات.
    // مثال: Setting::get('reviews.max_photos', 3).
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('settings.all', fn (): array => static::query()->pluck('value', 'key')->all());

        return array_key_exists($key, $all) && $all[$key] !== null ? $all[$key] : $default;
    }

    // يكتب إعدادًا ويمسح الكاش ليظهر التغيير فورًا.
    // القيمة تُحوَّل إلى نص.
    // تُستدعى من صفحة الإعدادات في الإدارة.
    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
        Cache::forget('settings.all');
    }
} 
