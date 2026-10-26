<?php

namespace App\Support;

/**
 * توحيد الأرقام إلى صيغة E.164 (مثل +967777123456).
 * لليمن: الجوال 9 أرقام تبدأ بـ 7.
 */
final class PhoneNumber
{
    private const DIGITS = [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ];

    /** يعيد الرقم بصيغة E.164 أو null إن كان غير صالح. */
    public static function normalize(?string $input, string $defaultCountryCode = '967'): ?string
    {
        $raw = trim(strtr((string) $input, self::DIGITS));

        if ($raw === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($raw, '+')) {
            $international = $digits;
        } elseif (str_starts_with($digits, '00')) {
            $international = substr($digits, 2);
        } elseif (str_starts_with($digits, $defaultCountryCode) && strlen($digits) > strlen($defaultCountryCode) + 6) {
            $international = $digits;
        } else {
            $international = $defaultCountryCode . ltrim($digits, '0');
        }

        if (preg_match('/^[1-9]\d{7,14}$/', $international) !== 1) {
            return null;
        }

        // اليمن: 967 + 9 أرقام تبدأ بـ 7
        if (str_starts_with($international, '967') && preg_match('/^9677\d{8}$/', $international) !== 1) {
            return null;
        }

        return '+' . $international;
    }

    /** الأرقام فقط دون + (الصيغة التي يطلبها رابط wa.me). */
    public static function digits(string $e164): string
    {
        return ltrim($e164, '+');
    }
}
