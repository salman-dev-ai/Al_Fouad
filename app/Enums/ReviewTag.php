<?php

namespace App\Enums;

// وسوم التعليق: قائمة مغلقة يختار منها العميل (لا نص حر فلا سبام ولا روابط).
// القيم النصية هي المخزَّنة في عمود tags (JSON)، والتسمية العربية للعرض.
// إضافة وسم جديد = إضافة case هنا فقط.
enum ReviewTag: string
{
    case Quality = 'quality';
    case Appearance = 'appearance';
    case Value = 'value';
    case Delivery = 'delivery';
    case Packaging = 'packaging';
    case Service = 'service';

    public function getLabel(): string
    {
        return match ($this) {
            self::Quality => 'جودة المنتج',
            self::Appearance => 'مطابق للصور',
            self::Value => 'قيمة مقابل السعر',
            self::Delivery => 'سرعة التوصيل',
            self::Packaging => 'التغليف',
            self::Service => 'خدمة العملاء',
        };
    }
}
