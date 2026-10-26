<?php
// لا نقبل أي رابط عشوائي، لأن الرابط سيظهر لك في لوحة الإدارة وفي رسالة واتساب، فنقبل خرائط Google فقط (حماية من روابط التصيّد
namespace App\Support;

final class GoogleMapsUrl
{
    public static function isValid(?string $url): bool
    {
        if ($url === null || $url === '' || strlen($url) > 2048 || preg_match('/\s/', $url) === 1) {
            return false;
        }

        $parts = parse_url($url);

        if ($parts === false
            || ($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            return false;
        }

        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '/';

        return match ($host) {
            'www.google.com', 'google.com' => str_starts_with($path, '/maps'),
            'maps.google.com', 'maps.app.goo.gl' => true,
            'goo.gl' => str_starts_with($path, '/maps'),
            default => false,
        };
    }
}
