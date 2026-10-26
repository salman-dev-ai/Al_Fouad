<?php

namespace Tests\Unit;

use App\Support\GoogleMapsUrl;
use PHPUnit\Framework\TestCase;

class GoogleMapsUrlTest extends TestCase
{
    public function test_accepts_google_maps_links(): void
    {
        foreach ([
            'https://www.google.com/maps?q=15.369445,44.191006',
            'https://maps.app.goo.gl/abc123',
            'https://maps.google.com/?q=1,2',
            'https://goo.gl/maps/xyz',
        ] as $url) {
            $this->assertTrue(GoogleMapsUrl::isValid($url), $url);
        }
    }

    public function test_rejects_everything_else(): void
    {
        foreach ([
            null,
            '',
            'http://www.google.com/maps?q=1,2',
            'https://evil.com/maps',
            'https://www.google.com@evil.com/maps',
            'https://www.google.com.evil.com/maps',
            'https://www.google.com/search?q=x',
            'https://maps.app.goo.gl:8080/abc',
            'javascript:alert(1)',
            "https://maps.app.goo.gl/a b",
        ] as $url) {
            $this->assertFalse(GoogleMapsUrl::isValid($url), var_export($url, true));
        }
    }
}
