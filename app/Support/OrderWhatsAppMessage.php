<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Collection;

/**
 * يُبنى النص من الطلب المحفوظ في قاعدة البيانات، لا مما يرسله المتصفح.
 */
final class OrderWhatsAppMessage
{
    /** حدّ طول النص بعد الترميز في الرابط؛ نقتطع الأصناف الزائدة إن تجاوزناه. */
    private const MAX_ENCODED_LENGTH = 4500;


    
    public static function url(Order $order, string $storePhoneE164, string $currency): string
    {
        return 'https://wa.me/' . PhoneNumber::digits($storePhoneE164)
            . '?text=' . rawurlencode(self::text($order, $currency));
    }

    public static function text(Order $order, string $currency): string
    {
        $items = $order->items->values();
        $total = $items->count();

        for ($shown = $total; $shown >= 1; $shown--) {
            $text = self::compose($order, $currency, $items->take($shown), $total - $shown);

            if ($shown === 1 || strlen(rawurlencode($text)) <= self::MAX_ENCODED_LENGTH) {
                return $text;
            }
        }

        return self::compose($order, $currency, $items, 0);
    }

    private static function compose(Order $order, string $currency, Collection $items, int $hidden): string
    {
        $lines = [
            'طلب جديد من متجر ' . config('app.name'),
            'رقم الطلب: ' . $order->order_number,
            '',
            'الأصناف:',
        ];

        foreach ($items as $i => $item) {
            $lines[] = ($i + 1) . ') ' . $item->product_name . ' — ' . $item->color . ' / ' . $item->size
                . ' × ' . $item->quantity . ' = ' . Money::format($item->line_total, $currency);
        }

        if ($hidden > 0) {
            $lines[] = "... و{$hidden} أصناف أخرى (التفاصيل في لوحة الإدارة)";
        }

        $lines[] = '';
        $lines[] = 'المجموع: ' . Money::format($order->subtotal, $currency);

        if (Money::toMinor($order->tax_rate) > 0) {
            $lines[] = 'الضريبة: ' . Money::format($order->tax_amount, $currency);
        }

        $paymentLabel = match ($order->payment_method) {
    'cod' => 'الدفع عند الاستلام',
    default => $order->payment_method,
};




        $lines[] = 'التوصيل: ' . (Money::toMinor($order->delivery_fee) > 0 ? Money::format($order->delivery_fee, $currency) : 'مجانًا');
        $lines[] = 'الإجمالي: ' . Money::format($order->total, $currency);
        $lines[] = 'طريقة الدفع: ' . $paymentLabel;
        // $lines[] = 'طريقة الدفع: ' . $order->payment_method->getLabel()::PaymentMethod::class ;
        $lines[] = '';
        $lines[] = 'الاسم: ' . $order->customer_name;
        $lines[] = 'الهاتف: ' . $order->customer_phone;

        if (filled($order->city)) {
            $lines[] = 'المدينة: ' . $order->city;
        }

        $lines[] = 'العنوان: ' . $order->delivery_address;

        if (filled($order->notes)) {
            $lines[] = 'ملاحظات: ' . $order->notes;
        }

        if (filled($order->location_url)) {
            $lines[] = 'موقع العميل: ' . $order->location_url;
        }

        return implode("\n", $lines);
    }
}
