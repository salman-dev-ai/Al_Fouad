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
    <x-store.breadcrumbs :items="[
        ['label' => 'الرئيسية', 'url' => route('home')],
        ['label' => 'سلة التسوق', 'url' => route('cart')],
        ['label' => 'إتمام الطلب'],
    ]" />

    <h1 class="mt-4 text-2xl font-extrabold sm:text-3xl">إتمام الطلب</h1>

    @if ($summary && ! $summary->isEmpty())
        <form wire:submit="placeOrder" novalidate class="mt-6 grid items-start gap-8 lg:grid-cols-[1fr_24rem]">
            <div class="space-y-6">

                @guest
                    <p class="rounded-lg bg-brand-50 p-4 text-sm">
                        لديك حساب؟
                        <a href="{{ route('login', ['next' => 'checkout']) }}" wire:navigate class="font-bold text-brand-700 underline underline-offset-4">سجّل دخولك</a>
                        أو
                        <a href="{{ route('register', ['next' => 'checkout']) }}" wire:navigate class="font-bold text-brand-700 underline underline-offset-4">أنشئ حسابًا</a>
                        لتُملأ بياناتك تلقائيًا في كل طلب.
                    </p>
                @endguest

                @if ($this->addresses->isNotEmpty())
                    <fieldset class="space-y-3 rounded-lg border border-line p-5">
                        <legend class="px-1 text-lg font-extrabold">عنوان الشحن</legend>

                        @foreach ($this->addresses as $address)
                            <label wire:key="choice-{{ $address->id }}" class="block cursor-pointer">
                                <input type="radio" wire:model.live="addressChoice" value="{{ $address->id }}" class="peer sr-only">
                                <span class="block rounded-md border border-line p-4 text-sm peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-500">
                                    <span class="font-bold">
                                        {{ $address->label ?: 'عنوان' }}
                                        @if ($address->is_default)
                                            <span class="ms-1 rounded-sm bg-brand-700 px-1.5 py-0.5 text-xs text-white">افتراضي</span>
                                        @endif
                                    </span>
                                    <span class="block text-ink-soft">{{ $address->recipient_name }} — {{ $address->city }}، {{ $address->address_line }}</span>
                                </span>
                            </label>
                        @endforeach

                        <label class="block cursor-pointer">
                            <input type="radio" wire:model.live="addressChoice" value="" class="peer sr-only">
                            <span class="block rounded-md border border-line p-4 text-sm font-bold peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-500">
                                + عنوان آخر
                            </span>
                        </label>
                    </fieldset>
                @endif

                <section class="space-y-4 rounded-lg border border-line p-5" aria-labelledby="delivery-heading">
                    <h2 id="delivery-heading" class="text-lg font-extrabold">بيانات الاستلام</h2>
                    @if ($this->addresses->isNotEmpty())
                        <p class="text-sm text-ink-soft">يمكنك تعديل أي بيانات لهذا الطلب دون تغيير عنوانك المحفوظ.</p>
                    @endif

                    <x-store.field name="form.recipient_name" label="الاسم الكامل" required autocomplete="name"
                                   wire:model.blur="form.recipient_name" />

                    <x-store.field name="form.phone" label="رقم الجوال" required type="tel" inputmode="tel" dir="ltr"
                                   placeholder="777123456" autocomplete="tel"
                                   helper="سنتواصل معك على هذا الرقم لتأكيد الطلب." wire:model.blur="form.phone" />

                    <x-store.field name="email" label="البريد الإلكتروني" type="email" dir="ltr"
                                   autocomplete="email" wire:model.blur="email" />

                    <x-store.field name="form.city" label="المدينة" required autocomplete="address-level2"
                                   wire:model.blur="form.city" />

                    <x-store.field name="form.address_line" as="textarea" label="العنوان بالتفصيل" required
                                   placeholder="الحي، الشارع، أقرب معلم..." autocomplete="street-address"
                                   wire:model.blur="form.address_line" />

                    <x-store.field name="form.notes" as="textarea" label="ملاحظات للتوصيل" wire:model.blur="form.notes" />
                </section>

                <section class="space-y-3 rounded-lg border border-line p-5" aria-labelledby="location-heading">
                    <h2 id="location-heading" class="text-lg font-extrabold">موقعك على الخريطة <span class="text-sm font-normal text-ink-soft">(اختياري)</span></h2>
                    <p class="text-sm text-ink-soft">يساعد المندوب في الوصول إليك بسرعة.</p>

                    <x-store.location-field model="form.location_url" />
                </section>

                @auth
                    @if ($addressChoice === '')
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="saveAddress" class="size-4 rounded border-line accent-brand-600">
                            احفظ هذا العنوان في حسابي لأستخدمه في طلباتي القادمة
                        </label>
                    @endif
                @endauth

                <section class="space-y-3 rounded-lg border border-line p-5" aria-labelledby="payment-heading">
                    <h2 id="payment-heading" class="text-lg font-extrabold">طريقة الدفع</h2>
                    <div class="flex items-center gap-3 rounded-md border-2 border-brand-600 bg-brand-50 p-4">
                        <x-lucide-banknote class="size-6 shrink-0 text-brand-700" aria-hidden="true" />
                        <div>
                            <p class="font-bold">الدفع عند الاستلام</p>
                            <p class="text-sm text-ink-soft">ادفع نقدًا عند استلام طلبك.</p>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="space-y-4 rounded-lg border border-line bg-surface p-5 lg:sticky lg:top-24" aria-labelledby="summary-heading">
                <h2 id="summary-heading" class="text-lg font-extrabold">ملخص الطلب</h2>

                <ul class="divide-y divide-line text-sm">
                    @foreach ($summary->lines as $line)
                        <li class="flex justify-between gap-3 py-2">
                            <span class="min-w-0">
                                <span class="block font-semibold">{{ $line['variant']->product->name }}</span>
                                <span class="text-xs text-ink-soft">{{ $line['variant']->color }} / {{ $line['variant']->size }} × {{ $line['quantity'] }}</span>
                            </span>
                            <span class="whitespace-nowrap font-semibold">{{ \App\Support\Money::formatMinor($line['line_minor'], $currency) }}</span>
                        </li>
                    @endforeach
                </ul>

                <x-store.totals :subtotal="$summary->subtotalMinor"
                                :tax="$summary->taxEnabled ? $summary->taxMinor : null" :tax-rate="$summary->taxRate"
                                :delivery="$summary->deliveryFeeMinor" :total="$summary->totalMinor" :currency="$currency" />

                @error('checkout')
                    <p class="rounded-md border border-danger/30 bg-white px-3 py-2 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                @enderror

                @if ($summary->hasBlockingIssues())
                    <p class="rounded-md border border-danger/30 bg-white px-3 py-2 text-sm font-semibold text-danger" role="alert">
                        بعض العناصر لم تعد متاحة.
                        <a href="{{ route('cart') }}" wire:navigate class="underline">راجع السلة</a>
                    </p>
                @endif

                <button type="submit" wire:loading.attr="disabled" wire:target="placeOrder"
                        @disabled($summary->hasBlockingIssues())
                        class="w-full rounded-md bg-brand-600 px-6 py-3.5 text-base font-extrabold text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-ink-soft/40">
                    <span wire:loading.remove wire:target="placeOrder">تأكيد الطلب</span>
                    <span wire:loading wire:target="placeOrder">جارٍ إرسال الطلب…</span>
                </button>

                <p class="text-xs text-ink-soft">
                    بتأكيد الطلب توافق على حفظ بياناتك لتنفيذ طلبك والتواصل معك بشأنه.
                </p>
            </aside>
        </form>
    @endif
</div>