<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class ResolveCart
{
    public const COOKIE = 'cart_token';

    private const TTL_MINUTES = 60 * 24 * 30;

    private ?Cart $cart = null;

    private bool $resolved = false;

    /** سلة الزائر الحالية إن وُجدت، دون إنشاء شيء. */
    public function find(): ?Cart
    {
        if ($this->resolved) {
            return $this->cart;
        }

        $this->resolved = true;
        $token = request()->cookie(self::COOKIE);

        // نتحقق من شكل الرمز قبل أي استعلام
        if (is_string($token) && preg_match('/^[A-Za-z0-9]{40}$/', $token) === 1) {
            $this->cart = Cart::query()->where('session_id', hash('sha256', $token))->first();
        }

        return $this->cart;
    }

    public function findOrCreate(): Cart
    {
        if ($cart = $this->find()) {
            return $cart;
        }

        $token = Str::random(40);

        $this->cart = Cart::create(['session_id' => hash('sha256', $token)]);

        Cookie::queue(Cookie::make(
            name: self::COOKIE,
            value: $token,
            minutes: self::TTL_MINUTES,
            path: '/',
            domain: null,
            secure: request()->isSecure(),
            httpOnly: true,
            raw: false,
            sameSite: 'lax',
        ));

        return $this->cart;
    }
}
