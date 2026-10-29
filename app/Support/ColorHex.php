<?php

namespace App\Support;

final class ColorHex
{
    /** يعيد #rrggbb بأحرف صغيرة (ويوسّع #rgb)، أو null إن كانت القيمة غير صالحة. */
    public static function normalize(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));

        if (preg_match('/^#([0-9a-f]{6})$/', $value) === 1) {
            return $value;
        }

        if (preg_match('/^#([0-9a-f])([0-9a-f])([0-9a-f])$/', $value, $m) === 1) {
            return "#{$m[1]}{$m[1]}{$m[2]}{$m[2]}{$m[3]}{$m[3]}";
        }

        return null;
    }

    /** هل الأفضل وضع نص داكن فوق هذا اللون؟ (نقطة التعادل في تباين WCAG = 0.179). */
    public static function isLight(string $hex): bool
    {
        $hex = ltrim($hex, '#');

        $linear = static function (int $channel): float {
            $c = $channel / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };

        $luminance = 0.2126 * $linear((int) hexdec(substr($hex, 0, 2)))
            + 0.7152 * $linear((int) hexdec(substr($hex, 2, 2)))
            + 0.0722 * $linear((int) hexdec(substr($hex, 4, 2)));

        return $luminance > 0.179;
    }
}