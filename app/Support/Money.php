<?php

namespace App\Support;

/**
 * الحسابات تتم بأعداد صحيحة (وحدات صغرى = المبلغ × 100) لتفادي أخطاء الأعداد العشرية.
 * دوال format* للعرض فقط.
 */
final class Money
{
    private const LABELS = ['YER' => 'ريال', 'SAR' => 'ر.س', 'USD' => '$'];

    /** عدد الخانات العشرية للعرض؛ الريال اليمني بلا كسور. */
    private const DECIMALS = ['YER' => 0];

    /** "12000.50" => 1200050. المدخل غير الصالح أو السالب يُعاد 0. */
    public static function toMinor(string|int|float|null $amount): int
    {
        $amount = trim((string) $amount);

        if (preg_match('/^\d+(\.\d+)?$/', $amount) !== 1) {
            return 0;
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return (int) $whole * 100 + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    /** 1200050 => "12000.50" (الصيغة المناسبة لأعمدة decimal). */
    public static function fromMinor(int $minor): string
    {
        return intdiv($minor, 100) . '.' . str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function format(string|int|float|null $amount, string $currency): string
    {
        $decimals = self::DECIMALS[$currency] ?? 2;
        $label = self::LABELS[$currency] ?? $currency;

        return number_format((float) $amount, $decimals) . ' ' . $label;
    }

    public static function formatMinor(int $minor, string $currency): string
    {
        return self::format(self::fromMinor($minor), $currency);
    }
}
