<?php

use App\Actions\Orders\PlaceOrder;
use App\Actions\Cart\ResolveCart;
use App\Actions\Cart\SummarizeCart;
use App\Models\StoreSetting;
use App\Support\CartSummary;
use App\Support\GoogleMapsUrl;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('إتمام الطلب')] class extends Component {
    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $city = '';

    public string $address = '';

    public string $notes = '';

    public string $location_url = '';

    public function mount(): void
    {
        if (! $this->summary || $this->summary->isEmpty()) {
            $this->redirectRoute('cart', navigate: true);
        }
    }

    #[Computed]
    public function currency(): string
    {
        return StoreSetting::current()?->currency_code ?? 'YER';
    }

    #[Computed]
    public function summary(): ?CartSummary
    {
        $cart = app(ResolveCart::class)->find();

        return $cart ? app(SummarizeCart::class)->execute($cart) : null;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'phone' => ['required', 'string', 'max:30', function (string $attribute, mixed $value, \Closure $fail): void {
                if (PhoneNumber::normalize((string) $value, config('store.default_country_code')) === null) {
                    $fail('أدخل رقم جوال صحيحًا، مثل 777123456.');
                }
            }],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'min:5', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'location_url' => ['nullable', 'string', 'max:2048', function (string $attribute, mixed $value, \Closure $fail): void {
                if (filled($value) && ! GoogleMapsUrl::isValid((string) $value)) {
                    $fail('أدخل رابطًا صحيحًا من خرائط Google، أو استخدم زر «استخدم موقعي الحالي».');
                }
            }],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'string' => 'قيمة :attribute غير صالحة.',
            'min' => 'حقل :attribute قصير جدًا (الحد الأدنى :min أحرف).',
            'max' => 'حقل :attribute طويل جدًا (الحد الأقصى :max).',
            'email' => 'أدخل بريدًا إلكترونيًا صحيحًا.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => 'الاسم',
            'phone' => 'رقم الجوال',
            'email' => 'البريد الإلكتروني',
            'city' => 'المدينة',
            'address' => 'العنوان',
            'notes' => 'الملاحظات',
            'location_url' => 'رابط الموقع',
        ];
    }

    /** تحقق فوري من كل حقل عند مغادرته. */
    public function updated(string $property): void
    {
        $this->validateOnly($property);
    }

    public function placeOrder(PlaceOrder $placeOrder, ResolveCart $carts): void
    {
        $validated = $this->validate();

        $cart = $carts->find();

        if (! $cart) {
            $this->redirectRoute('cart', navigate: true);

            return;
        }

        $ipKey = 'place-order-ip:' . request()->ip();
        $cartKey = 'place-order-cart:' . $cart->id;

        if (RateLimiter::tooManyAttempts($ipKey, 30) || RateLimiter::tooManyAttempts($cartKey, 5)) {
            $this->addError('form', 'محاولات كثيرة، حاول بعد قليل.');

            return;
        }

        RateLimiter::hit($ipKey, 600);
        RateLimiter::hit($cartKey, 600);

        try {
            $order = $placeOrder->execute($cart, [
                'name' => trim($validated['name']),
                'phone' => PhoneNumber::normalize($validated['phone'], config('store.default_country_code')),
                'email' => filled($validated['email']) ? mb_strtolower(trim($validated['email'])) : null,
                'city' => trim($validated['city']),
                'address' => trim($validated['address']),
                'notes' => filled($validated['notes']) ? trim($validated['notes']) : null,
                'location_url' => filled($validated['location_url']) ? trim($validated['location_url']) : null,
            ]);
        } catch (ValidationException $e) {
            $this->addError('form', $e->validator->errors()->first());

            return;
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

                <section class="space-y-4 rounded-lg border border-line p-5" aria-labelledby="delivery-heading">
                    <h2 id="delivery-heading" class="text-lg font-extrabold">بيانات الاستلام</h2>

                    <x-store.field name="name" label="الاسم الكامل" required autocomplete="name" wire:model.blur="name" />

                    <x-store.field name="phone" label="رقم الجوال" required type="tel" inputmode="tel" dir="ltr"
                                   placeholder="777123456" autocomplete="tel"
                                   helper="سنتواصل معك على هذا الرقم لتأكيد الطلب." wire:model.blur="phone" />

                    <x-store.field name="email" label="البريد الإلكتروني" type="email" dir="ltr"
                                   autocomplete="email" wire:model.blur="email" />

                    <x-store.field name="city" label="المدينة" required autocomplete="address-level2" wire:model.blur="city" />

                    <x-store.field name="address" as="textarea" label="العنوان بالتفصيل" required
                                   placeholder="الحي، الشارع، أقرب معلم..." autocomplete="street-address"
                                   wire:model.blur="address" />

                    <x-store.field name="notes" as="textarea" label="ملاحظات للتوصيل"
                                   wire:model.blur="notes" />
                </section>

                {{-- الموقع: Alpine للتفاعل المحلي فقط، والخادم يتحقق من الرابط --}}
                <section class="space-y-3 rounded-lg border border-line p-5" aria-labelledby="location-heading"
                         x-data="{
                            locating: false,
                            message: '',
                            locate() {
                                if (!navigator.geolocation) { this.message = 'متصفحك لا يدعم تحديد الموقع.'; return; }
                                this.locating = true;
                                this.message = '';
                                navigator.geolocation.getCurrentPosition(
                                    (pos) => {
                                        this.$wire.location_url = 'https://www.google.com/maps?q=' + pos.coords.latitude.toFixed(6) + ',' + pos.coords.longitude.toFixed(6);
                                        this.locating = false;
                                        this.message = 'تم تحديد موقعك. يمكنك تعديل الرابط أو حذفه.';
                                    },
                                    () => {
                                        this.locating = false;
                                        this.message = 'تعذّر تحديد موقعك. اسمح للمتصفح بالوصول للموقع أو الصق رابطًا يدويًا.';
                                    },
                                    { enableHighAccuracy: true, timeout: 10000 }
                                );
                            }
                         }">
                    <h2 id="location-heading" class="text-lg font-extrabold">موقعك على الخريطة <span class="text-sm font-normal text-ink-soft">(اختياري)</span></h2>
                    <p class="text-sm text-ink-soft">يساعد المندوب في الوصول إليك بسرعة.</p>

                    <button type="button" @click="locate()" :disabled="locating"
                            class="flex items-center gap-2 rounded-md border border-brand-600 px-4 py-2.5 text-sm font-bold text-brand-700 hover:bg-brand-50 disabled:opacity-60">
                        <x-lucide-map-pin class="size-4" aria-hidden="true" />
                        <span x-text="locating ? 'جارٍ تحديد الموقع…' : 'استخدم موقعي الحالي'">استخدم موقعي الحالي</span>
                    </button>
                    <p x-show="message" x-cloak x-text="message" class="text-sm text-ink-soft" role="status"></p>

                    <x-store.field name="location_url" label="رابط الموقع" type="url" dir="ltr"
                                   placeholder="https://maps.app.goo.gl/..."
                                   helper="يمكنك لصق رابط من تطبيق خرائط Google."
                                   wire:model.blur="location_url" />
                </section>

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

            {{-- الملخص وزر التأكيد --}}
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

                @error('form')
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
