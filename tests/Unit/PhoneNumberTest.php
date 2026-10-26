<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    public function test_normalizes_yemeni_numbers_in_all_common_formats(): void
    {
        foreach (['777123456', '0777123456', '+967 777 123 456', '00967777123456', '967777123456', '77-712-3456', '٠٧٧٧١٢٣٤٥٦'] as $input) {
            $this->assertSame('+967777123456', PhoneNumber::normalize($input), "فشل: {$input}");
        }
    }

    public function test_accepts_other_countries_with_explicit_prefix(): void
    {
        $this->assertSame('+966501234567', PhoneNumber::normalize('+966501234567'));
    }

    public function test_rejects_invalid_numbers(): void
    {
        foreach ([null, '', 'abc', '12345', '+967123456789', '+9677771234', '0777'] as $input) {
            $this->assertNull(PhoneNumber::normalize($input), 'يجب رفض: ' . var_export($input, true));
        }
    }

    public function test_digits_strips_plus_sign(): void
    {
        $this->assertSame('967777123456', PhoneNumber::digits('+967777123456'));
    }
}
