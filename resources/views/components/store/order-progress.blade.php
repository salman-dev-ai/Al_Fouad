{{-- مراحل الطلب: قيد الانتظار ← مؤكد ← تم الشحن ← تم التسليم، والمراحل المنجزة بعلامة ✓. --}}
{{-- الطلب الملغي يعرض رسالة واحدة بدل المراحل. --}}
{{-- المرحلة الحالية تحمل aria-current ليقرأها قارئ الشاشة. --}}
@props(['status'])

@php
    $steps = [
        \App\Enums\OrderStatus::Pending,
        \App\Enums\OrderStatus::Confirmed,
        \App\Enums\OrderStatus::Shipped,
        \App\Enums\OrderStatus::Delivered,
    ];
    $currentIndex = array_search($status, $steps, true);
@endphp

@if ($status === \App\Enums\OrderStatus::Cancelled)
    <p class="rounded-md bg-red-100 px-4 py-3 text-sm font-bold text-red-900" role="status">تم إلغاء هذا الطلب.</p>
@else
    <ol class="grid grid-cols-4 gap-2 text-center text-xs sm:text-sm" aria-label="مراحل الطلب">
        @foreach ($steps as $i => $step)
            @php($reached = $currentIndex !== false && $i <= $currentIndex)
            <li class="space-y-2" @if ($i === $currentIndex) aria-current="step" @endif>
                <span class="mx-auto grid size-8 place-items-center rounded-full {{ $reached ? 'bg-brand-600 text-white' : 'bg-surface text-ink-soft' }}">
                    @if ($reached)
                        <x-lucide-check class="size-4" aria-hidden="true" />
                    @else
                        {{ $i + 1 }}
                    @endif
                </span>
                <span class="block {{ $reached ? 'font-bold text-ink' : 'text-ink-soft' }}">{{ $step->getLabel() }}</span>
            </li>
        @endforeach
    </ol>
@endif