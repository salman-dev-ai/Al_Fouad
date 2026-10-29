<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Support\ColorHex;
use Illuminate\Support\Collection;
use App\Observers\ProductVariantObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;


#[ObservedBy(ProductVariantObserver::class)]
class ProductVariant extends Model
{

    /** قائمة المقاسات المعتمدة وترتيب عرضها. المصدر الوحيد للمتجر وللوحة الإدارة. */
    public const SIZES = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', 'Free Size'];
    protected $fillable = [
        'product_id',
        'size',
        'color',
        'price',
        'compare_at_price',
        'stock_quantity',
        'sku',
        'color_hex',
        'product_color_id'
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'stock_quantity' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function hasDiscount(): bool
    {
        return $this->compare_at_price !== null
            && $this->compare_at_price > $this->price;
    }

    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    /**
 * يجمّع الخيارات في «ألوان» فريدة: الاسم والدرجة وهل هو متوفر وهل هو فاتح.
 * حمّل الخيارات مسبقًا لتجنب استعلامات إضافية.
 *
 * @param  Collection<int, ProductVariant>  $variants
 * @return Collection<int, array{name: string, hex: ?string, in_stock: bool, light: bool}>
 */
public static function swatchesFrom(Collection $variants): Collection
{
    return $variants->groupBy('color')->map(function (Collection $group, string $name): array {
        $hex = ColorHex::normalize($group->pluck('color_hex')->filter()->first());

        return [
            'name' => $name,
            'hex' => $hex,
            'in_stock' => $group->sum('stock_quantity') > 0,
            'light' => $hex !== null && ColorHex::isLight($hex),
        ];
    })->values();
}


protected static function booted(): void
{
    static::saving(function (ProductVariant $variant): void {
        $color = null;

        if ($variant->product_color_id) {
            $color = ProductColor::query()->find($variant->product_color_id);
        } elseif ($variant->product_id && filled($variant->color)) {
            $name = trim($variant->color);

            $color = ProductColor::query()
                ->where('product_id', $variant->product_id)
                ->where('name', $name)
                ->first()
                ?? ProductColor::create([
                    'product_id' => $variant->product_id,
                    'name' => $name,
                    'hex' => $variant->color_hex,
                    'sort_order' => (int) ProductColor::query()->where('product_id', $variant->product_id)->max('sort_order') + 1,
                ]);
        }

        if ($color) {
            $variant->product_color_id = $color->id;
            $variant->product_id = $color->product_id;
            $variant->color = $color->name;
            $variant->color_hex = $color->hex;
        }
    });
}

public function productColor(): BelongsTo
{
    return $this->belongsTo(ProductColor::class);
}
}
