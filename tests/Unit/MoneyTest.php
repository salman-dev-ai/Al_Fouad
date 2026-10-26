<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_converts_decimal_strings_to_minor_units(): void
    {
        $this->assertSame(1200050, Money::toMinor('12000.50'));
        $this->assertSame(1200000, Money::toMinor('12000.00'));
        $this->assertSame(1200000, Money::toMinor(12000));
        $this->assertSame(5, Money::toMinor('0.05'));
        $this->assertSame(1050, Money::toMinor('10.5'));
    }

    public function test_invalid_or_negative_amounts_become_zero(): void
    {
        $this->assertSame(0, Money::toMinor(null));
        $this->assertSame(0, Money::toMinor('abc'));
        $this->assertSame(0, Money::toMinor('-5'));
    }

    public function test_converts_minor_units_back_to_decimal_strings(): void
    {
        $this->assertSame('12000.50', Money::fromMinor(1200050));
        $this->assertSame('0.05', Money::fromMinor(5));
        $this->assertSame('0.00', Money::fromMinor(0));
    }

    public function test_formats_for_display(): void
    {
        $this->assertSame('12,000 ريال', Money::format('12000.00', 'YER'));
        $this->assertSame('12,000.50 ر.س', Money::format('12000.50', 'SAR'));
    }
}
