<?php

use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('تسجيل الدخول')] class extends Component {
    public string $phone = '';

    public string $password = '';

    public bool $remember = false;


    // إن جاء العميل من صفحة إتمام الطلب (?next=checkout) نحفظ وجهته ليعود إليها بعد الدخول.
// القيمة الوحيدة المسموحة checkout (قائمة بيضاء)، فلا يمكن توجيه العميل لرابط خارجي.
// لا أثر لها على بقية الزيارات.
public function mount(): void
{
    if (request()->query('next') === 'checkout') {
        session()->put('url.intended', route('checkout'));
    }
}

    // يوحّد الرقم ويحاول الدخول به مع كلمة المرور، ويحدّ المحاولات الفاشلة (5 لكل رقم وIP).
    // الرسالة عامة دائمًا حتى لا يُكشف هل الرقم مسجّل أم لا.
    // عند النجاح يجدد الجلسة (منع تثبيت الجلسة) ويعيد العميل إلى وجهته الأصلية.
    public function login(): void
    {
        $this->validate(
            [
                'phone' => ['required', 'string', 'max:30'],
                'password' => ['required', 'string', 'max:255'],
            ],
            [
                'phone.required' => 'أدخل رقم الجوال.',
                'password.required' => 'أدخل كلمة المرور.',
            ],
        );

        $normalized = PhoneNumber::normalize($this->phone, config('store.default_country_code'));
        $key = 'login:' . ($normalized ?? 'invalid') . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('phone', 'محاولات كثيرة. حاول بعد ' . RateLimiter::availableIn($key) . ' ثانية.');

            return;
        }

        if ($normalized === null || ! Auth::attempt(['phone' => $normalized, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($key, 300);
            $this->addError('phone', 'رقم الجوال أو كلمة المرور غير صحيحة.');
            $this->reset('password');

            return;
        }

        RateLimiter::clear($key);
        session()->regenerate();

        $this->redirectIntended(default: route('account'), navigate: true);
    }
};
?>

<div class="mx-auto max-w-md px-4 py-12 sm:px-6">
    <h1 class="text-2xl font-extrabold sm:text-3xl">تسجيل الدخول</h1>
    <p class="mt-2 text-sm text-ink-soft">أدخل رقم جوالك وكلمة المرور.</p>

    <form wire:submit="login" novalidate class="mt-6 space-y-5 rounded-lg border border-line p-6">
        <x-store.field name="phone" label="رقم الجوال" required type="tel" inputmode="tel" dir="ltr"
                       placeholder="777123456" autocomplete="tel" wire:model="phone" />

        <x-store.field name="password" label="كلمة المرور" required type="password" dir="ltr"
                       autocomplete="current-password" wire:model="password" />

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="remember" class="size-4 rounded border-line accent-brand-600">
            تذكّرني على هذا الجهاز
        </label>

        <button type="submit" wire:loading.attr="disabled" wire:target="login"
                class="w-full rounded-md bg-brand-600 px-6 py-3 text-base font-extrabold text-white hover:bg-brand-700 disabled:opacity-60">
            <span wire:loading.remove wire:target="login">دخول</span>
            <span wire:loading wire:target="login">جارٍ الدخول…</span>
        </button>
    </form>

    <p class="mt-5 text-center text-sm text-ink-soft">
        ليس لديك حساب؟
        <a href="{{ route('register') }}" wire:navigate class="font-bold text-brand-700 underline underline-offset-4">أنشئ حسابًا</a>
    </p>
    <p class="mt-2 text-center text-xs text-ink-soft">نسيت كلمة المرور؟ تواصل معنا عبر واتساب لنعيد ضبطها لك.</p>
</div>