<?php

use App\Models\Order;
use App\Models\StoreSetting;
use App\Support\OrderWhatsAppMessage;
use App\Support\PhoneNumber;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('تم استلام طلبك')] class extends Component {
    public int $orderId;

    public function mount(Order $order): void
    {
        // لا تُعرض الصفحة إلا لمن أنشأ الطلب في جلسته، حتى لا تُخمَّن أرقام الطلبات
        abort_unless(in_array($order->order_number, session('placed_orders', []), true), 404);

        $this->orderId = $order->id;
    }

    #[Computed]
    public function order(): Order
    {
        $order = Order::query()->with('items')->findOrFail($this->orderId);

        abort_unless(in_array($order->order_number, session('placed_orders', []), true), 404);

        return $order;
    }

    #[Computed]
    public function currency(): string
    {
        return StoreSetting::current()?->currency_code ?? 'YER';
    }

    #[Computed]
    public function whatsappUrl(): ?string
    {
        $phone = PhoneNumber::normalize(
            StoreSetting::current()?->whatsapp_phone,
            config('store.default_country_code')
        );

        return $phone ? OrderWhatsAppMessage::url($this->order, $phone, $this->currency) : null;
    }
};
?>

@php
    $order = $this->order;
    $currency = $this->currency;
    $whatsappUrl = $this->whatsappUrl;
@endphp

<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="text-center">
        <x-lucide-circle-check class="mx-auto size-14 text-brand-600" aria-hidden="true" />
        <h1 class="mt-4 text-2xl font-extrabold sm:text-3xl">تم استلام طلبك بنجاح</h1>
        <p class="mt-2 text-ink-soft">
            رقم طلبك:
            <strong class="select-all text-lg text-ink" dir="ltr">{{ $order->order_number }}</strong>
        </p>
    </div>

    {{-- زر واتساب: الإجراء الأهم في الصفحة --}}
    @if ($whatsappUrl)
        <div class="mt-8 rounded-lg border-2 border-brand-600 bg-brand-50 p-6 text-center">
            <p class="font-bold">خطوة أخيرة: أرسل تفاصيل طلبك إلى المحل عبر واتساب</p>
            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
               class="mt-4 inline-flex w-full items-center justify-center gap-3 rounded-md bg-brand-600 px-6 py-4 text-lg font-extrabold text-white hover:bg-brand-700 sm:w-auto sm:min-w-80">
                <x-lucide-message-circle class="size-6" aria-hidden="true" />
                أرسل الطلب عبر واتساب
            </a>
            <p class="mt-3 text-sm text-ink-soft">
                سيفتح واتساب والرسالة جاهزة، اضغط «إرسال» فقط. طلبك محفوظ لدينا حتى لو لم ترسلها.
            </p>
        </div>
    @else
        <p class="mt-8 rounded-lg bg-brand-50 p-5 text-center font-semibold text-brand-700">
            سنتواصل معك قريبًا على رقمك لتأكيد الطلب.
        </p>
    @endif

    <section class="mt-8 space-y-5 rounded-lg border border-line p-5" aria-labelledby="details-heading">
        <h2 id="details-heading" class="text-lg font-extrabold">تفاصيل الطلب</h2>

        <ul class="divide-y divide-line text-sm">
            @foreach ($order->items as $item)
                <li class="flex justify-between gap-3 py-3">
                    <span class="min-w-0">
                        <span class="block font-bold">{{ $item->product_name }}</span>
                        <span class="text-xs text-ink-soft">{{ $item->color }} / {{ $item->size }} × {{ $item->quantity }}</span>
                    </span>
                    <span class="whitespace-nowrap font-semibold">{{ \App\Support\Money::format($item->line_total, $currency) }}</span>
                </li>
            @endforeach
        </ul>

        <x-store.totals :subtotal="\App\Support\Money::toMinor($order->subtotal)"
                        :tax="\App\Support\Money::toMinor($order->tax_rate) > 0 ? \App\Support\Money::toMinor($order->tax_amount) : null"
                        :tax-rate="$order->tax_rate"
                        :delivery="\App\Support\Money::toMinor($order->delivery_fee)"
                        :total="\App\Support\Money::toMinor($order->total)"
                        :currency="$currency" />
    </section>

    <section class="mt-6 rounded-lg border border-line p-5 text-sm" aria-labelledby="delivery-heading">
        <h2 id="delivery-heading" class="mb-3 text-lg font-extrabold">بيانات التوصيل</h2>
        <dl class="space-y-2">
            <div class="flex gap-3"><dt class="w-28 shrink-0 text-ink-soft">الاسم</dt><dd>{{ $order->customer_name }}</dd></div>
            <div class="flex gap-3"><dt class="w-28 shrink-0 text-ink-soft">الهاتف</dt><dd dir="ltr">{{ $order->customer_phone }}</dd></div>
            <div class="flex gap-3"><dt class="w-28 shrink-0 text-ink-soft">المدينة</dt><dd>{{ $order->city }}</dd></div>
            <div class="flex gap-3"><dt class="w-28 shrink-0 text-ink-soft">العنوان</dt><dd>{{ $order->delivery_address }}</dd></div>
            <div class="flex gap-3"><dt class="w-28 shrink-0 text-ink-soft">طريقة الدفع</dt><dd>{{ match ($order->payment_method) {
    'cod' => 'الدفع عند الاستلام',
    default => $order->payment_method ?? 'غير محدد',
} }}</dd></div>
        </dl>
    </section>

    <div class="mt-8 text-center">
        <a href="{{ route('products.index') }}" wire:navigate
           class="text-sm font-semibold text-brand-700 underline underline-offset-4">متابعة التسوق</a>
    </div>
</div>
