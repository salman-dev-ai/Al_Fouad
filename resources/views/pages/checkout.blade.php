<?php

use App\Actions\Cart\ResolveCart;
use App\Actions\Cart\SummarizeCart;
use App\Actions\Orders\PlaceOrder;
use App\Livewire\Forms\AddressForm;
use App\Models\Address;
use App\Models\StoreSetting;
use App\Support\CartSummary;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new
 #[Title('إتمام الطلب')] 
 class extends Component {
    public AddressForm $form;

    #[Validate('nullable|email|max:255', message: 'أدخل بريدًا إلكترونيًا صحيحًا.')]
    public string $email = '';

    // اختيار العنوان المحفوظ: '' يعني «عنوان آخر»، وأي رقم يعني عنوانًا من عناوين المستخدم.
    public string $addressChoice = '';

    public bool $saveAddress = false;

    // يجهّز الصفحة: يعيد من السلة الفارغة، وللمسجّل يملأ البيانات من عنوانه الافتراضي (أو من حسابه).
    // الزائر تبقى حقوله فارغة كما كان.
    // كل ما يُملأ قابل للتعديل قبل التأكيد.
    public function mount(): void
    {
        if (! $this->summary || $this->summary->isEmpty()) {
            $this->redirectRoute('cart', navigate: true);

            return;
        }

        $user = auth()->user();

        if (! $user) {
            return;
        }

        $this->email = (string) $user->email;

        $default = $this->addresses->firstWhere('is_default', true) ?? $this->addresses->first();

        if ($default) {
            $this->addressChoice = (string) $default->id;
            $this->fillFromAddress($default);
        } else {
            $this->form->recipient_name = $user->name;
            $this->form->phone = (string) $user->phone;
        }
    }

    #[Computed]
    public function currency(): string
    {
        return StoreSetting::current()?->currency_code ?? 'YER';
    }

    // ملخص السلة من مصدر الحساب الوحيد (SummarizeCart)، لا من أي رقم يرسله المتصفح.
    // يُعاد حسابه كل مرة من قاعدة البيانات.
    #[Computed]
    public function summary(): ?CartSummary
    {
        $cart = app(ResolveCart::class)->find();

        return $cart ? app(SummarizeCart::class)->execute($cart) : null;
    }

    // عناوين المستخدم الحالي فقط (الافتراضي أولًا)، وللزائر مجموعة فارغة.
    // تُستعمل لعرض اختيار العنوان.
    // لا تقبل أي معرّف مستخدم من المتصفح.
    #[Computed]
    public function addresses(): Collection
    {
        return auth()->check()
            ? auth()->user()->addresses()->orderByDesc('is_default')->orderByDesc('id')->get()
            : new Collection();
    }

    // عند تغيير اختيار العنوان: العنوان المحفوظ يملأ الحقول، و«عنوان آخر» يفرّغ حقول العنوان فقط.
    // الجلب من عناوين المستخدم نفسه (findOrFail)، فمعرّف غيره يعطي 404.
    // التعديل بعد التعبئة يخص هذا الطلب فقط ولا يغيّر العنوان المحفوظ.
    public function updatedAddressChoice(string $value): void
    {
        $this->resetValidation();
        $this->saveAddress = false;

        if ($value === '') {
            $this->form->city = '';
            $this->form->address_line = '';
            $this->form->notes = '';
            $this->form->location_url = '';

            return;
        }

        $this->fillFromAddress(auth()->user()->addresses()->findOrFail((int) $value));
    }

    // ينسخ حقول عنوان محفوظ إلى نموذج الإتمام (دون ربط معرّفه، حتى لا يُعدَّل المحفوظ بالخطأ).
    private function fillFromAddress(Address $address): void
    {
        $this->form->recipient_name = $address->recipient_name;
        $this->form->phone = $address->phone;
        $this->form->city = $address->city;
        $this->form->address_line = $address->address_line;
        $this->form->notes = (string) $address->notes;
        $this->form->location_url = (string) $address->location_url;
    }

    // يتحقق من الحقول ثم يضع الطلب عبر PlaceOrder (معاملة + قفل المخزون)، بحد معدل لكل IP ولكل سلة.
    // يربط الطلب بالحساب إن وُجد، ويحفظ العنوان في الحساب إن اختار العميل ذلك (فشل الحفظ لا يُفقد الطلب).
    // ينتقل بعدها إلى صفحة التأكيد المقصورة على جلسة صاحب الطلب.
    public function placeOrder(PlaceOrder $placeOrder, ResolveCart $carts): void
    {
        $this->validate();
        $this->form->validate();

        $cart = $carts->find();

        if (! $cart) {
            $this->redirectRoute('cart', navigate: true);

            return;
        }

        $ipKey = 'place-order-ip:' . request()->ip();
        $cartKey = 'place-order-cart:' . $cart->id;

        if (RateLimiter::tooManyAttempts($ipKey, 30) || RateLimiter::tooManyAttempts($cartKey, 5)) {
            $this->addError('checkout', 'محاولات كثيرة، حاول بعد قليل.');

            return;
        }

        RateLimiter::hit($ipKey, 600);
        RateLimiter::hit($cartKey, 600);

        $user = auth()->user();

        try {
            $order = $placeOrder->execute($cart, [
                'name' => trim($this->form->recipient_name),
                'phone' => PhoneNumber::normalize($this->form->phone, config('store.default_country_code')),
                'email' => filled($this->email) ? mb_strtolower(trim($this->email)) : null,
                'city' => trim($this->form->city),
                'address' => trim($this->form->address_line),
                'notes' => filled($this->form->notes) ? trim($this->form->notes) : null,
                'location_url' => filled($this->form->location_url) ? trim($this->form->location_url) : null,
            ], $user);
        } catch (ValidationException $e) {
            $this->addError('checkout', $e->validator->errors()->first());

            return;
        }

        if ($user && $this->saveAddress && $this->addressChoice === ''
            && $user->addresses()->count() < Address::MAX_PER_USER) {
            try {
                $this->form->save($user);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // صلاحية رؤية صفحة التأكيد: لمن أنشأ الطلب في جلسته فقط
        session()->put('placed_orders', array_slice([...session('placed_orders', []), $order->order_number], -20));

        $this->redirectRoute('order.confirmed', ['order' => $order->order_number], navigate: true);
    }
};
?>

@php
    $summary = $this->summary;
    $currency = $this->currency;
@endphp

{{-- صفحة إتمام الطلب: بيانات الاستلام (مع اختيار عنوان محفوظ للمسجّل) + الموقع + الدفع + ملخص الطلب. --}}
{{-- المنطق كله في الخادم (PlaceOrder)، وAlpine يظهر فقط داخل مكوّن الموقع. --}}
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <x-ui.breadcrumbs :items="[
        ['label' => 'الرئيسية', 'url' => route('home')],
        ['label' => 'سلة التسوق', 'url' => route('cart')],
        ['label' => 'إتمام الطلب'],
    ]" />

    <h1 class="mt-4 text-2xl font-extrabold sm:text-3xl">إتمام الطلب</h1>

    @if ($summary && ! $summary->isEmpty())
        <form wire:submit="placeOrder" novalidate class="mt-6 grid items-start gap-8 lg:grid-cols-[1fr_24rem]">
            <div class="space-y-6">

                @include('components.checkout.address-choice')

                @include('components.checkout.delivery-form')

                @include('components.checkout.location-form')

                @include('components.checkout.payment')
            </div>

            @include('components.checkout.summary')
        </form>
    @endif
</div>