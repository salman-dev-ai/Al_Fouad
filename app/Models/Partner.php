<?php

namespace App\Models;

use App\Models\Concerns\FlushesAboutCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

// شريك أو شركة يتعامل معها المحل، يظهر شعاره في شريط دائري بصفحة «قصتنا».
// الترتيب بـ sort_order (سحب وإفلات في الإدارة)، والإخفاء بـ is_active دون حذف.
// الشعار يُحذف من التخزين عند الاستبدال أو الحذف.
#[Fillable(['name', 'logo', 'url', 'sort_order', 'is_active'])]
class Partner extends Model
{
    use FlushesAboutCache;

    // الشركاء الظاهرون بالترتيب المعتمد.
    // يستعمله بناء بيانات الصفحة.
    // مثال: Partner::query()->visible()->get().
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    protected function aboutImageColumns(): array
    {
        return ['logo'];
    }


    protected function casts(): array
    {
        return [
          'sort_order' => 'integer',
           'is_active' => 'boolean'
        ];
    }
}
