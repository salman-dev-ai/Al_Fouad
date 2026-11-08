{{-- نجوم عرض فقط (لا تفاعل): تُلوَّن النجمة إن بلغ التقييم ما يقارب رقمها (ربع نجمة تسامح). التقييم 0 = كل النجوم فارغة. --}}
{{-- الوصولية: عنصر واحد role=img بوصف نصي، والنجوم نفسها مخفية عن قارئ الشاشة. --}}
{{-- الاستعمال: <x-store.rating-stars :rating="4.5" size="size-5" />. --}}
@props(['rating' => 0, 'size' => 'size-4'])

<span {{ $attributes->class('inline-flex items-center gap-0.5') }} role="img"
      aria-label="التقييم {{ number_format((float) $rating, 1) }} من 5">
    @for ($i = 1; $i <= 5; $i++)
        <x-lucide-star :class="\Illuminate\Support\Arr::toCssClasses([$size, 'fill-brand-600 text-brand-600' => $rating >= $i - 0.25, 'text-line' => $rating < $i - 0.25])" aria-hidden="true" />
    @endfor
</span>
