<?php

namespace App\Models;

use App\Support\ColorHex;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductColor extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'hex',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // نسخ الاسم والدرجة إلى خيارات هذا اللون (الأعمدة القديمة التي تقرأها السلة والطلبات)
        static::saved(function (ProductColor $color): void {
            if ($color->wasChanged(['name', 'hex'])) {
                $color->variants()->update(['color' => $color->name, 'color_hex' => $color->hex]);
            }
        });



        // حذف اللون يحذف مقاساته (الصور تُحذف بمفتاح الربط في قاعدة البيانات)
        static::deleting(function (ProductColor $color): void {
            $color->variants()->delete();
        });
    }

    /** أي قيمة غير صالحة تصبح null، ولا تُخزَّن إلا #rrggbb. */
    protected function hex(): Attribute
    {
        return Attribute::set(fn(?string $value): ?string => ColorHex::normalize($value));
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }
}
