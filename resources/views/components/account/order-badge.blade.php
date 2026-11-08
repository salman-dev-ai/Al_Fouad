{{-- شارة ملوّنة لحالة الطلب بتباين مقروء، والنص هو اسم الحالة العربي من الـ Enum. --}}
{{-- الألوان هنا لواجهة المتجر (Tailwind)، وألوان لوحة الإدارة تأتي من getColor() في الـ Enum. --}}
{{-- إضافة حالة جديدة للـ Enum تستلزم إضافة سطر لها في match أدناه. --}}
@props(['status'])

@php
    $classes = match ($status) {
        \App\Enums\OrderStatus::Pending => 'bg-amber-100 text-amber-900',
        \App\Enums\OrderStatus::Confirmed => 'bg-blue-100 text-blue-900',
        \App\Enums\OrderStatus::Shipped => 'bg-indigo-100 text-indigo-900',
        \App\Enums\OrderStatus::Delivered => 'bg-brand-50 text-brand-700',
        \App\Enums\OrderStatus::Cancelled => 'bg-red-100 text-red-900',
    };
@endphp

<span {{ $attributes->class(['inline-block rounded-full px-3 py-1 text-xs font-bold', $classes]) }}>{{ $status->getLabel() }}</span>