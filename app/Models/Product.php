<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'is_featured',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }





    /**
     * الألوان المتاحة مستنتجة من الخيارات. حمّل العلاقة variants مسبقًا
     * (with('variants')) لتجنب استعلامات إضافية.
     * الغرض منه استخراج
     * الألوان المتاحة للمنتج من الخيارات الموجودة في جدول
     * product_variants، مع إزالة الألوان المكررة
     */
    protected function availableColors(): Attribute
    {
        return Attribute::get(
            fn (): Collection => $this->variants->pluck('color')->unique()->values()
        );
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }



        /** أرخص خيار. يتطلب تحميل variants مسبقًا لتجنب N+1. */
    public function cheapestVariant(): ?ProductVariant
    {
        return $this->variants
            ->sortBy(fn (ProductVariant $variant): float => (float) $variant->price)
            ->first();
    }

    /** الصورة الرئيسية، وإن لم تُحدَّد فأولى الصور. يتطلب تحميل images مسبقًا. */
    protected function mainImage(): Attribute
    {
        return Attribute::get(
            fn (): ?ProductImage => $this->images->firstWhere('is_primary', true) ?? $this->images->first()
        );
    }

    /** هل يتوفر أي خيار؟ يتطلب تحميل variants مسبقًا. */
    protected function inStock(): Attribute
    {
        return Attribute::get(
            fn (): bool => $this->variants->contains(fn (ProductVariant $variant): bool => $variant->stock_quantity > 0)
        );
    }
}
