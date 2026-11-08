<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

// حالات التقييم: قيد المراجعة، معتمد (يظهر ويدخل المتوسط)، مرفوض (لا يظهر).
// يطبّق واجهتي Filament لتظهر الحالة كشارة ملونة بالعربية تلقائيًا في الجداول.
// القيم النصية هي المخزَّنة في قاعدة البيانات.
enum ReviewStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'قيد المراجعة',
            self::Approved => 'معتمد',
            self::Rejected => 'مرفوض',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
