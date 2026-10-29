<?php

use App\Models\Order;
use App\Models\StoreSetting;
use App\Support\GoogleMapsUrl;
use App\Support\PhoneNumber;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('تفاصيل الطلب')] class extends Component {
    #[Locked]
    public int $orderId;

    // يجد الطلب من طلبات المستخدم الحالي فقط، ويعطي 404 لرقم لا يخصه (أو لطلب زائر) دون كشف وجوده.
    // يحفظ المعرّف الداخلي المقفل (Locked) ليعيد الجلب في كل تحديث.
    // لا يعتمد على أي صلاحية يرسلها المتصفح.
    public function mount(string $number): void
    {
        $this->orderId = auth()->user()->orders()->where('order_number', $number)->firstOrFail()->id;
    }

    // الطلب بأصنافه (لقطة وقت الشراء)، يُجلب دائمًا من طلبات المستخدم الحالي (حماية مضاعفة).
    #[Computed]
    public function order(): Order
    {
        return auth()->user()->orders()->with('items')->findOrFail($this->orderId);
    }

    // عملة الطلب وقت الشراء، وإن غابت فعملة المحل الحالية.
    #[Computed]
    public function currency(): string
    {
        return $this->order->currency_code ?: (StoreSetting::current()?->currency_code ?? 'YER');
    }

    // رابط واتساب للاستفسار عن هذا الطلب، برسالة جاهزة فيها رقم الطلب.
    // يُبنى من رقم واتساب المحل في الإعدادات، ويختفي إن لم يُضبط.
    // الرابط مجاني (wa.me) ولا يرسل شيئًا تلقائيًا.
    #[Computed]
    public function whatsappUrl(): ?string
    {
        $phone = PhoneNumber::normalize(StoreSetting::current()?->whatsapp_phone, config('store.default_country_code'));

        return $phone
            ? 'https://wa.me/' . PhoneNumber::digits($phone) . '?text=' . rawurlencode('مرحبًا، أستفسر عن طلبي رقم ' . $this->order->order_number)
            : null;
    }
};
?>

@php
    $order = $this->order;
    $currency = $this->currency;
    $whatsappUrl = $this->whatsappUrl;
@endphp

{{-- تفاصيل طلب واحد: الحالة والمراحل، الأصناف بأسعارها وقت الشراء، المبالغ، بيانات التوصيل والدفع. --}}
{{-- كل البيانات من لقطة الطلب المحفوظة، فلا تتغير بتغيير المنتجات لاحقًا. --}}
<x-store.account-shell heading="طلب {{ $order->order_number }}">
    <div class="space-y-6">
        <a href="{{ route('account.orders') }}" wire:navigate
           class="inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:underline">
            <x-lucide-chevron-right class="size-4" aria-hidden="true" /> كل طلباتي
        </a>

        <section class="space-y-4 rounded-lg border border-line p-5" aria-labelledby="status-heading">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 id="status-heading" class="text-lg font-extrabold">حالة الطلب</h2>
                <x-store.order-badge :status="$order->status" />
            </div>
            <p class="text-xs text-ink-soft">تاريخ الطلب: {{ $order->created_at->translatedFormat('j F Y، h:i A') }}</p>
            <x-store.order-progress :status="$order->status" />
        </section>

        <section class="rounded-lg border border-line p-5" aria-labelledby="items-heading">
            <h2 id="items-heading" class="mb-3 text-lg font-extrabold">المنتجات</h2>

            <ul class="divide-y divide-line text-sm">
                @foreach ($order->items as $item)
                    <li class="flex justify-between gap-3 py-3">
                        <span class="min-w-0">
                            <span class="block font-bold">{{ $item->product_name }}</span>
                            <span class="text-xs text-ink-soft">{{ $item->color }} / {{ $item->size }}</span>
                            <span class="block text-xs text-ink-soft">
                                {{ \App\Support\Money::format($item->unit_price, $currency) }} × {{ $item->quantity }}
                            </span>
                        </span>
                        <span class="whitespace-nowrap font-semibold">{{ \App\Support\Money::format($item->line_total, $currency) }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4 border-t border-line pt-4">
                <x-store.totals :subtotal="\App\Support\Money::toMinor($order->subtotal)"
                                :tax="\App\Support\Money::toMinor($order->tax_rate) > 0 ? \App\Support\Money::toMinor($order->tax_amount) : null"
                                :tax-rate="$order->tax_rate"
                                :delivery="\App\Support\Money::toMinor($order->delivery_fee)"
                                :total="\App\Support\Money::toMinor($order->total)"
                                :currency="$currency" />
            </div>
        </section>

        <section class="rounded-lg border border-line p-5 text-sm" aria-labelledby="delivery-heading">
            <h2 id="delivery-heading" class="mb-3 text-lg font-extrabold">بيانات التوصيل والدفع</h2>
            <dl class="space-y-2">
                <div class="flex gap-3"><dt class="w-28 shrink-0 text-ink-soft">المستلم</dt><dd>{{ $order->customer_name }}</dd></div>
                <div class="flex gap-3"><dt class="w-28 shrink-0 text-ink-soft">الجوال</dt><dd dir="ltr">{{ $order->customer_phone }}</dd></div>
                <div class="flex gap-3"><dt class="w-28 shrink-0 text-ink-soft">المدينة</dt><dd>{{ $order->city }}</dd></div>
                <div class="flex gap-3"><dt class="w-28 shrink-0 text-ink-soft">العنوان</dt><dd>{{ $order->delivery_address }}</dd></div>
                @if ($order->notes)
                    <div class="flex gap-3"><dt class="w-28 shrink-0 text-ink-soft">ملاحظات</dt><dd>{{ $order->notes }}</dd></div>
                @endif
                @if ($order->location_url && \App\Support\GoogleMapsUrl::isValid($order->location_url))
                    <div class="flex gap-3">
                        <dt class="w-28 shrink-0 text-ink-soft">الموقع</dt>
                        <dd><a href="{{ $order->location_url }}" target="_blank" rel="noopener noreferrer"
                               class="font-semibold text-brand-700 underline underline-offset-4">عرض على الخريطة</a></dd>
                    </div>
                @endif
                <div class="flex gap-3"><dt class="w-28 shrink-0 text-ink-soft">طريقة الدفع</dt><dd>{{ match ($order->payment_method) {
        'cod' => 'الدفع عند الاستلام',
        default => $order->payment_method,
    } }}</dd></div>
            </dl>
        </section>

        @if ($whatsappUrl)
            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center gap-2 rounded-md border border-brand-600 px-5 py-3 font-bold text-brand-700 hover:bg-brand-50">
                <x-lucide-message-circle class="size-5" aria-hidden="true" />
                استفسر عن هذا الطلب عبر واتساب
            </a>
        @endif
    </div>
</x-store.account-shell>