@props(['subtotal', 'delivery', 'total', 'currency', 'tax' => null, 'taxRate' => null])

<dl class="space-y-2 text-sm">
    <div class="flex justify-between gap-3">
        <dt class="text-ink-soft">المجموع الفرعي</dt>
        <dd class="font-semibold">{{ \App\Support\Money::formatMinor($subtotal, $currency) }}</dd>
    </div>

    @if ($tax !== null)
        <div class="flex justify-between gap-3">
            <dt class="text-ink-soft">الضريبة ({{ rtrim(rtrim((string) $taxRate, '0'), '.') }}%)</dt>
            <dd class="font-semibold">{{ \App\Support\Money::formatMinor($tax, $currency) }}</dd>
        </div>
    @endif

    <div class="flex justify-between gap-3">
        <dt class="text-ink-soft">رسوم التوصيل</dt>
        <dd class="font-semibold">{{ $delivery > 0 ? \App\Support\Money::formatMinor($delivery, $currency) : 'مجانًا' }}</dd>
    </div>

    <div class="flex justify-between gap-3 border-t border-line pt-3 text-base">
        <dt class="font-extrabold">الإجمالي</dt>
        <dd class="font-extrabold text-brand-700">{{ \App\Support\Money::formatMinor($total, $currency) }}</dd>
    </div>
</dl>
