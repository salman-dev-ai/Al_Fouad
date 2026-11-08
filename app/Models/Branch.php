<?php

namespace App\Models;

use App\Models\Concerns\FlushesAboutCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

// فرع من فروع المحل: عنوان، هاتف، واتساب، رابط خريطة Google، وأوقات العمل كنص حر.
// رابط الخريطة يُتحقق منه بـ GoogleMapsUrl (روابط Google https فقط) قبل الحفظ.
// يظهر في قسم «فروعنا» بصفحة قصتنا، ويصلح لاحقًا للتذييل.
#[Fillable(['name', 'city', 'address', 'phone', 'whatsapp_phone', 'map_url', 'hours_text', 'image', 'sort_order', 'is_active'])]
 class Branch extends Model
{
    use FlushesAboutCache;

    // الفروع الظاهرة بالترتيب المعتمد.
    // يستعمله بناء بيانات الصفحة.
    // مثال: Branch::query()->visible()->get().
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    protected function aboutImageColumns(): array
    {
        return ['image'];
    }


    protected function casts(): array
    {
        return [
          'sort_order' => 'integer',
           'is_active' => 'boolean'
        ];
    }
}
