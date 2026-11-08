{{-- شريط تقدم الشحن المجاني: يظهر فقط حين يكون الحد مفعّلًا والسلة غير فارغة. --}}
{{-- النسبة تُحسب في الخادم بأعداد صحيحة وتُقصّ عند 100، فلا تحتاج لأي منطق في Alpine. --}}
{{-- الوصولية: role=progressbar مع قيم وصفية، والرسالة داخل role=status لتُقرأ عند تغيّرها. --}}
@props(['summary', 'currency'])

@if ($summary->freeShippingThresholdMinor !== null && ! $summary->isEmpty())
    @php
        $reached = $summary->qualifiesForFreeShipping();
        $percent = min(100, intdiv($summary->subtotalMinor * 100, $summary->freeShippingThresholdMinor));
    @endphp

    <div class="rounded-lg border border-line bg-white p-4" role="status">
        <p class="flex items-center gap-2 text-sm font-semibold">
            @if ($reached)
                <x-lucide-circle-check class="size-5 shrink-0 text-brand-700" aria-hidden="true" />
                <span>مبروك! توصيل طلبك مجاني.</span>
            @else
                <x-lucide-truck class="size-5 shrink-0 text-brand-700" aria-hidden="true" />
                <span>أضف بقيمة {{ \App\Support\Money::formatMinor($summary->freeShippingRemainingMinor, $currency) }} للحصول على توصيل مجاني.</span>
            @endif
        </p>

        <div class="mt-3 h-2 overflow-hidden rounded-full bg-surface"
             role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}"
             aria-label="التقدم نحو الشحن المجاني">
            <div class="h-full rounded-full bg-brand-600 motion-safe:transition-[width] motion-safe:duration-300"
                 style="width: {{ $percent }}%"></div>
        </div>
    </div>
@endif
