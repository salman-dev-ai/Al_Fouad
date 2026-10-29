<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = [
        'phone',
        'whatsapp_phone',
        'email',
        'address',
        'map_url',
        'delivery_fee',
        'tax_enabled',
        'tax_rate',
        'currency_code',
        'opening_hours',
        'social_links',
        'announcement_text',
        'announcement_enabled',
        'maintenance_mode',
        'free_shipping_threshold',
    ];

    protected function casts(): array
    {
        return [
            'delivery_fee' => 'decimal:2',
            'free_shipping_threshold' => 'decimal:2',
            'tax_enabled' => 'boolean',
            'tax_rate' => 'decimal:2',
            'opening_hours' => 'array',
            'social_links' => 'array',
            'announcement_enabled' => 'boolean',
            'maintenance_mode' => 'boolean',
        ];
    }

    /**
     * سجل إعدادات المحل الوحيد، أو null إن لم يُنشأ بعد.
     */
    public static function current(): ?self
    {
        return static::query()->first();
    }
}
