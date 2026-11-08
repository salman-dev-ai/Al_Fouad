<?php

namespace App\Models;

use App\Models\Concerns\FlushesAboutCache;
 use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

// رأي عميل منتقى يديره المدير ويظهر في شريط «ماذا يقول عملاؤنا».
// منفصل عن تقييمات المنتجات (تلك مرتبطة بشراء فعلي وتعرض تحت كل منتج).
// الصورة اختيارية، وتُحذف عند الاستبدال أو الحذف.
#[Fillable(['name', 'subtitle', 'quote', 'rating', 'avatar', 'sort_order', 'is_active'])]

class Testimonial extends Model
{
    use FlushesAboutCache;

    // الآراء الظاهرة بالترتيب المعتمد.
    // يستعمله بناء بيانات الصفحة.
    // مثال: Testimonial::query()->visible()->get().
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    protected function aboutImageColumns(): array
    {
        return ['avatar'];
    }

    protected function casts(): array
    {
        return [
          'rating' => 'integer',
          'sort_order' => 'integer',
          'is_active' => 'boolean'
        ];
    }
}
