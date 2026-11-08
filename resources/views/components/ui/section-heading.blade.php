{{-- عنوان قسم موحّد: سطر صغير ملوّن فوق عنوان كبير وخط زخرفي قصير، ويتمركز اختياريًا. --}}
{{-- سيُعاد استعماله في الرئيسية وبقية الصفحات لثبات الهوية البصرية. --}}
{{-- الاستعمال: <x-ui.section-heading eyebrow="شركاؤنا" title="نعمل مع الأفضل" :center="true" />. --}}
@props(['title', 'eyebrow' => null, 'center' => false])

<div @class(['max-w-2xl', 'mx-auto text-center' => $center])>
    @if ($eyebrow)
        <p class="text-sm font-bold text-brand-700">{{ $eyebrow }}</p>
    @endif
    <h2 class="mt-1 text-2xl font-extrabold sm:text-3xl">{{ $title }}</h2>
    <span @class(['mt-3 block h-1 w-12 rounded-full bg-brand-600', 'mx-auto' => $center]) aria-hidden="true"></span>
</div>
