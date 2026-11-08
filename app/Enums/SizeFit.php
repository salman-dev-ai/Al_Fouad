<?php

namespace App\Enums;

// ملاءمة المقاس كما رآها العميل: ضيّق، مناسب، واسع.
// قائمة مغلقة تُخزَّن داخل size_data (JSON) ضمن بيانات المقاس الاختيارية.
// التسمية العربية للعرض والنموذج.
enum SizeFit: string
{
    case Small = 'small';
    case True = 'true';
    case Large = 'large';

    public function getLabel(): string
    {
        return match ($this) {
            self::Small => 'ضيّق',
            self::True => 'مناسب',
            self::Large => 'واسع',
        };
    }
}
