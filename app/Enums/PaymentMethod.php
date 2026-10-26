<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case CashOnDelivery = 'cod';

    public function getLabel(): string
    {
        return match ($this) {
            self::CashOnDelivery => 'الدفع عند الاستلام',
        };
    }
}
