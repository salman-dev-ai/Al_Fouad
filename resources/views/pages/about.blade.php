<?php

use App\Actions\About\BuildAboutPageData;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('قصتنا')] class extends Component {
    // يتأكد أن الصفحة منشورة وله محتوى، وإلا يعيد 404 (غير موجودة) للزائر.
    // البيانات تأتي من الكاش بلا استعلامات في الطلبات المتكررة.
    // الإدارة تتحكم بالنشر من «محتوى الصفحة».
    public function mount(): void
    {
        abort_unless($this->content['published'], 404);
    }

    // بيانات الصفحة الجاهزة (مصفوفة نصوص ومسارات)، وتُحسب مرة لكل طلب فقط.
    // مصدرها الكاش الذي يمسحه أي تعديل في الإدارة.
    // لا تحتوي كائنات Eloquent فلا N+1 ولا استعلام خفي في العرض.
    #[Computed]
    public function content(): array
    {
        return app(BuildAboutPageData::class)->cached();
    }
};
?>

@php
    $c = $this->content;
    $show = $c['show'];
    $store = $c['store'];
    $url = fn (?string $path): ?string => $path ? \Illuminate\Support\Facades\Storage::disk('public')->url($path) : null;

    // أصناف تنسيق نص Markdown (بدل إضافة Typography): كلها حروف ثابتة ليلتقطها Tailwind.
    $prose = '[&_p]:mt-4 [&_p]:leading-8 [&_h2]:mt-6 [&_h2]:text-xl [&_h2]:font-extrabold [&_h3]:mt-5 [&_h3]:font-extrabold [&_ul]:mt-4 [&_ul]:list-disc [&_ul]:ps-6 [&_ol]:mt-4 [&_ol]:list-decimal [&_ol]:ps-6 [&_li]:mt-1.5 [&_a]:font-semibold [&_a]:text-brand-700 [&_a]:underline [&_strong]:font-bold [&>*:first-child]:mt-0';

    $statCols = [1 => 'sm:grid-cols-1', 2 => 'sm:grid-cols-2', 3 => 'sm:grid-cols-3', 4 => 'sm:grid-cols-4'];
    $socialNames = ['facebook' => 'فيسبوك', 'instagram' => 'إنستغرام', 'tiktok' => 'تيك توك', 'x' => 'إكس', 'snapchat' => 'سناب شات', 'telegram' => 'تيليجرام'];

    // بيانات منظمة لمحركات البحث (متجر + أسئلة شائعة)، تُبنى من البيانات المعقّمة نفسها.
    $ld = [
        '@context' => 'https://schema.org',
        '@graph' => array_values(array_filter([
            array_filter([
                '@type' => 'Store',
                'name' => config('app.name'),
                'url' => url('/'),
                'description' => $c['seo_description'],
                'image' => $url($c['hero']['image']),
                'telephone' => $store['phone_digits'] ? '+' . $store['phone_digits'] : null,
                'email' => $store['email'],
                'address' => $store['address'] ? ['@type' => 'PostalAddress', 'streetAddress' => $store['address']] : null,
                'sameAs' => $store['social'] ? array_values($store['social']) : null,
            ]),
            $c['faqs'] ? [
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn ($faq) => [
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
                ], $c['faqs']),
            ] : null,
        ])),
    ];

    // هل تُعرض أول قيمة كبطاقة مميزة (عندما تكون القيم أكثر من اثنتين).
    $featureFirst = count($c['values'] ?? []) > 2;

    // أصناف مشتركة لأزرار الإجراء الصغيرة في الفروع.
    $chip = 'inline-flex items-center gap-1.5 rounded-full border border-line bg-white px-4 py-2 text-sm font-semibold transition hover:border-brand-500 hover:text-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500';
@endphp

{{-- وصف الصفحة لمحركات البحث: يظهر إن كان القالب يحوي @stack('head') داخل <head>. --}}
@push('head')
    @if ($c['seo_description'])
        <meta name="description" content="{{ $c['seo_description'] }}">
    @endif
@endpush

<div class="overflow-x-clip">
    @include('components.about.hero')

    @include('components.about.stats')

    @include('components.about.story')

    @include('components.about.values')

    @include('components.about.partners')

    @include('components.about.testimonials')

    @include('components.about.branches')

    @include('components.about.payments')

    @include('components.about.returns')

    @include('components.about.faqs')

    @include('components.about.contact')

    @include('components.about.cta')

    {{-- بيانات منظمة لمحركات البحث: HEX تمنع أي نص من إغلاق وسم script. --}}
    <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
</div>
