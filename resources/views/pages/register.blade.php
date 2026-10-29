<?php

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;

new 
#[Title('إنشاء حساب')] 
class extends Component {
    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';


    // إن جاء العميل من صفحة إتمام الطلب (?next=checkout) نحفظ وجهته ليعود إليها بعد الدخول.
// القيمة الوحيدة المسموحة checkout (قائمة بيضاء)، فلا يمكن توجيه العميل لرابط خارجي.
// لا أثر لها على بقية الزيارات.
public function mount(): void
{
    if (request()->query('next') === 'checkout') {
        session()->put('url.intended', route('checkout'));
    }
}

    // قواعد التسجيل: الاسم والجوال إلزاميان (والجوال فريد بعد توحيده)، والبريد اختياري، وكلمة المرور 8 أحرف فأكثر.
    // التحقق من تفرّد الجوال يتم على الصيغة الموحّدة، فلا يتكرر الرقم بصيغ مختلفة.
    // يُعاد نفس النص لكل مستخدم، ولا تُكشف بيانات أي حساب آخر.
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'phone' => ['required', 'string', 'max:30', function (string $attribute, mixed $value, \Closure $fail): void {
                $normalized = PhoneNumber::normalize((string) $value, config('store.default_country_code'));

                if ($normalized === null) {
                    $fail('أدخل رقم جوال صحيحًا، مثل 777123456.');
                } elseif (User::query()->where('phone', $normalized)->exists()) {
                    $fail('هذا الرقم مسجّل مسبقًا. جرّب تسجيل الدخول.');
                }
            }],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    // رسائل التحقق بالعربية لكل قاعدة.
    // تُستخدم أسماء الحقول المعرّفة في validationAttributes أدناه.
    protected function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'min' => 'حقل :attribute قصير جدًا.',
            'max' => 'حقل :attribute طويل جدًا.',
            'email' => 'أدخل بريدًا إلكترونيًا صحيحًا.',
            'email.unique' => 'هذا البريد مسجّل مسبقًا.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
        ];
    }

    // أسماء الحقول كما تظهر في رسائل الأخطاء.
    protected function validationAttributes(): array
    {
        return [
            'name' => 'الاسم',
            'phone' => 'رقم الجوال',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
        ];
    }

    // ينشئ حساب عميل عاديًا (ليس مديرًا أبدًا) برقم موحّد، ثم يدخله فورًا.
    // محدود بـ 5 تسجيلات كل ساعة لكل IP لمنع إنشاء حسابات وهمية بالجملة.
    // تجديد الجلسة بعد الدخول يمنع هجمات تثبيت الجلسة.
    public function register(): void
    {
        $validated = $this->validate();

        $key = 'register:' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('name', 'محاولات كثيرة. حاول لاحقًا.');

            return;
        }

        RateLimiter::hit($key, 3600);

        $user = User::create([
            'name' => trim($validated['name']),
            'phone' => PhoneNumber::normalize($validated['phone'], config('store.default_country_code')),
            'email' => filled($validated['email']) ? mb_strtolower(trim($validated['email'])) : null,
            'password' => $validated['password'],
        ]);

        Auth::login($user);
        session()->regenerate();

        $this->redirectIntended(default: route('account'), navigate: true);
    }
};
?>

<div class="mx-auto max-w-md px-4 py-12 sm:px-6">
    <h1 class="text-2xl font-extrabold sm:text-3xl">إنشاء حساب</h1>
    <p class="mt-2 text-sm text-ink-soft">احفظ بياناتك وعناوينك لتُكمل طلباتك بأسرع وقت.</p>

    <form wire:submit="register" novalidate class="mt-6 space-y-5 rounded-lg border border-line p-6">
        <x-store.field name="name" label="الاسم الكامل" required autocomplete="name" wire:model="name" />

        <x-store.field name="phone" label="رقم الجوال" required type="tel" inputmode="tel" dir="ltr"
                       placeholder="777123456" autocomplete="tel" wire:model="phone"
                       helper="تدخل به إلى حسابك، وسنتواصل معك عليه بشأن طلباتك." />

        <x-store.field name="email" label="البريد الإلكتروني" type="email" dir="ltr"
                       autocomplete="email" wire:model="email" />

        <x-store.field name="password" label="كلمة المرور" required type="password" dir="ltr"
                       autocomplete="new-password" helper="8 أحرف على الأقل." wire:model="password" />

        <x-store.field name="password_confirmation" label="تأكيد كلمة المرور" required type="password" dir="ltr"
                       autocomplete="new-password" wire:model="password_confirmation" />

        <button type="submit" wire:loading.attr="disabled" wire:target="register"
                class="w-full rounded-md bg-brand-600 px-6 py-3 text-base font-extrabold text-white hover:bg-brand-700 disabled:opacity-60">
            <span wire:loading.remove wire:target="register">إنشاء الحساب</span>
            <span wire:loading wire:target="register">جارٍ الإنشاء…</span>
        </button>
    </form>

    <p class="mt-5 text-center text-sm text-ink-soft">
        لديك حساب؟
        <a href="{{ route('login') }}" wire:navigate class="font-bold text-brand-700 underline underline-offset-4">سجّل دخولك</a>
    </p>
</div>