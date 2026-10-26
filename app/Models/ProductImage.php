<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'image_path',
        'alt_text',
        'sort_order',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_primary' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }


        /** رابط الصورة العام، يعتمد على عنوان الطلب الحالي فلا يتعطل إن اختلف APP_URL. */
    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => asset('storage/' . $this->image_path));
    }
}
