<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
