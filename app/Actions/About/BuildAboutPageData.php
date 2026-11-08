<?php

namespace App\Actions\About;

use App\Models\AboutPage;
use App\Models\Branch;
use App\Models\Partner;
use App\Models\StoreSetting;
use App\Models\Testimonial;
use App\Support\AboutIcons;
use App\Support\GoogleMapsUrl;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

// يجمع بيانات صفحة «قصتنا» كلها في مصفوفة بسيطة جاهزة للعرض، ويخزّنها مؤقتًا حتى أول تعديل من الإدارة.
// يعقّم كل شيء هنا مرة واحدة: Markdown بلا HTML خام، روابط https فقط، أيقونات من القائمة البيضاء، أرقام مطبّعة.
// يُمسح الكاش تلقائيًا من خاصية FlushesAboutCache، فالزائر لا يرى بيانات قديمة ولا تُنفَّذ استعلامات في كل طلب.
class BuildAboutPageData
{
    // يعيد البيانات من الكاش (دائم حتى المسح)، ويبنيها عند أول طلب.
    // نخزّن مسارات الصور لا روابطها الكاملة، فلا يفسد الكاش إن تغيّر العنوان (APP_URL).
    // هذه هي الدالة التي تستدعيها الصفحة.
    public function cached(): array
    {
        return Cache::rememberForever(AboutPage::CACHE_KEY, fn (): array => $this->build());
    }


        // نسخة مصغّرة من بيانات الصفحة للرأس والتذييل: هل نُشرت، وروابط الأقسام الموجودة فعلًا، وأسماء الفروع وطرق الدفع.
    // تُخزَّن مدخلًا مستقلًا صغيرًا فلا يقرأ كل طلب صفحة «قصتنا» الكاملة، ويُمسح مع أي تعديل (FlushesAboutCache).
    // تعيد published=false ومصفوفات فارغة إن لم يُنشر المحتوى، فلا يظهر شيء في القالب.
    public function footer(): array
    {
        return Cache::rememberForever(AboutPage::FOOTER_CACHE_KEY, function (): array {
            $data = $this->build();
            $show = $data['show'];

            return [
                'published' => $data['published'],
                'has_returns' => $show['returns'],
                'return_days' => $data['returns']['days'],
                'has_payments' => $show['payments'],
                'payment_names' => array_values(array_column(array_slice($data['payments']['methods'], 0, 4), 'name')),
                'has_faqs' => $show['faqs'],
                'has_branches' => $show['branches'],
                'branches' => array_map(
                    fn (array $b): array => ['name' => $b['name'], 'city' => $b['city']],
                    array_slice($data['branches'], 0, 4),
                ),
            ];
        });
    }

    // يبني المصفوفة من قاعدة البيانات: المحتوى المفرد، الشركاء، الآراء، الفروع، ومعلومات المحل.
    // لكل قسم علامة show تجمع شرطين: اختاره المدير، وله محتوى فعلي، فالفارغ لا يُرسم أبدًا.
    // published تكون false إن لم يُحفظ محتوى بعد أو أوقفه المدير (الصفحة تعيد 404).
    public function build(): array
    {
        $page = AboutPage::current();
        $settings = StoreSetting::current();
        $chosen = $page?->visible_sections ?? [];

        $stats = $this->rows($page?->stats, ['value', 'label']);
        $values = collect($this->rows($page?->core_values, ['title', 'text']))
            ->map(fn (array $row): array => $row + ['icon' => 'sparkles'])
            ->map(fn (array $row): array => ['icon' => AboutIcons::isAllowed($row['icon']) ? $row['icon'] : 'sparkles'] + $row)
            ->all();
        $methods = collect($this->rows($page?->payment_methods, ['name']))
            ->map(fn (array $row): array => [
                'name' => $row['name'],
                'description' => $row['description'] ?? null,
                'icon' => AboutIcons::isAllowed($row['icon'] ?? null) ? $row['icon'] : 'wallet',
            ])->all();
        $faqs = $this->rows($page?->faqs, ['question', 'answer']);

        $partners = Partner::query()->visible()->get(['id', 'name', 'logo', 'url'])
            ->map(fn (Partner $p): array => ['name' => $p->name, 'logo' => $p->logo, 'url' => $this->httpsUrl($p->url)])
            ->all();

        $testimonials = Testimonial::query()->visible()->get(['id', 'name', 'subtitle', 'quote', 'rating', 'avatar'])
            ->map(fn (Testimonial $t): array => [
                'name' => $t->name,
                'subtitle' => $t->subtitle,
                'quote' => $t->quote,
                'rating' => max(1, min(5, (int) $t->rating)),
                'avatar' => $t->avatar,
            ])->all();

        $branches = Branch::query()->visible()->get()
            ->map(fn (Branch $b): array => [
                'name' => $b->name,
                'city' => $b->city,
                'address' => $b->address,
                'phone' => $b->phone,
                'phone_digits' => $this->digits($b->phone),
                'whatsapp_digits' => $this->digits($b->whatsapp_phone),
                'map_url' => GoogleMapsUrl::isValid($b->map_url) ? $b->map_url : null,
                'hours_text' => $b->hours_text,
                'image' => $b->image,
            ])->all();

        $store = [
            'phone' => $settings?->phone,
            'phone_digits' => $this->digits($settings?->phone),
            'whatsapp_digits' => $this->digits($settings?->whatsapp_phone),
            'email' => $settings?->email,
            'address' => $settings?->address,
            'map_url' => GoogleMapsUrl::isValid($settings?->map_url) ? $settings->map_url : null,
            'hours' => collect((array) ($settings?->opening_hours ?? []))
                ->map(fn ($row): array => [
                    'day' => (string) ($row['day'] ?? ''),
                    'closed' => (bool) ($row['is_closed'] ?? false),
                    'opens' => (string) ($row['opens_at'] ?? ''),
                    'closes' => (string) ($row['closes_at'] ?? ''),
                ])->filter(fn (array $row): bool => $row['day'] !== '')->values()->all(),
            'social' => collect((array) ($settings?->social_links ?? []))
                ->map(fn ($url): ?string => $this->httpsUrl(is_string($url) ? $url : null))
                ->filter()->all(),
        ];

        $story = [
            'title' => $this->text($page?->story_title),
            'body_html' => $this->markdown($page?->story_body),
            'image' => $page?->story_image,
            'mission' => $this->text($page?->mission),
            'vision' => $this->text($page?->vision),
        ];

        $hero = [
            'title' => $this->text($page?->hero_title),
            'subtitle' => $this->text($page?->hero_subtitle),
            'image' => $page?->hero_image,
        ];

        $shippingHtml = $this->markdown($page?->shipping_info);
        $returnsHtml = $this->markdown($page?->return_policy);
        $returnDays = $page?->return_days ? (int) $page->return_days : null;

        $has = fn (string $key, bool $content): bool => in_array($key, $chosen, true) && $content;

        return [
            'published' => $page !== null && $page->is_published,
            'seo_description' => $this->text($page?->seo_description),
            'hero' => $hero,
            'story' => $story,
            'stats' => $stats,
            'values' => $values,
            'partners' => $partners,
            'testimonials' => $testimonials,
            'branches' => $branches,
            'payments' => ['methods' => $methods, 'shipping_html' => $shippingHtml],
            'returns' => ['days' => $returnDays, 'html' => $returnsHtml],
            'faqs' => $faqs,
            'store' => $store,
            'show' => [
                'hero' => $hero['title'] !== null || $hero['subtitle'] !== null || $hero['image'] !== null,
                'story' => $has('story', $story['title'] !== null || $story['body_html'] !== null || $story['mission'] !== null || $story['vision'] !== null),
                'stats' => $has('stats', $stats !== []),
                'values' => $has('values', $values !== []),
                'partners' => $has('partners', $partners !== []),
                'testimonials' => $has('testimonials', $testimonials !== []),
                'branches' => $has('branches', $branches !== []),
                'payments' => $has('payments', $methods !== [] || $shippingHtml !== null),
                'returns' => $has('returns', $returnDays !== null || $returnsHtml !== null),
                'faqs' => $has('faqs', $faqs !== []),
                'contact' => $has('contact', filled($store['phone']) || filled($store['email']) || filled($store['address']) || $store['hours'] !== [] || $store['social'] !== []),
            ],
        ];
    }

    // يحوّل قائمة JSON من الإدارة إلى صفوف نظيفة: يحذف الصف الناقص أحد حقوله المطلوبة، ويعيد الترقيم.
    // يحتفظ بكل الحقول الأخرى كنص مقصوص، ويتجاهل أي شيء ليس مصفوفة.
    // يستعمله بناء الأرقام والمميزات والدفع والأسئلة.
    private function rows(mixed $items, array $required): array
    {
        return collect(is_array($items) ? $items : [])
            ->filter(fn ($row): bool => is_array($row) && collect($required)->every(fn (string $key): bool => filled($row[$key] ?? null)))
            ->map(fn (array $row): array => array_map(fn ($value): mixed => is_string($value) ? trim($value) : $value, $row))
            ->values()
            ->all();
    }

    // يحوّل Markdown إلى HTML آمن: بلا HTML خام (يُحذف)، وبلا روابط خطرة مثل javascript:.
    // يُنفَّذ هنا عند بناء الكاش فقط، فلا تكلفة تحويل في الطلبات المتكررة.
    // يعيد null للنص الفارغ، والناتج وحده هو ما يُطبع بلا تهريب في الصفحة.
    private function markdown(?string $text): ?string
    {
        $clean = trim((string) $text);

        return $clean === ''
            ? null
            : (string) Str::markdown($clean, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }

    // يقصّ النص ويعيد null إن كان فارغًا، لتسهيل شروط العرض.
    // لا يغيّر المحتوى (التهريب يتم في Blade).
    // يُستعمل للعناوين والجمل القصيرة.
    private function text(?string $text): ?string
    {
        $clean = trim((string) $text);

        return $clean === '' ? null : $clean;
    }

    // يقبل روابط https فقط، ويعيد null لغيرها (http، javascript:، data:...).
    // حماية إضافية في العرض فوق التحقق الذي في الإدارة (قد تُعدَّل القاعدة مباشرة).
    // تُستعمل لروابط الشركاء والتواصل الاجتماعي.
    private function httpsUrl(?string $url): ?string
    {
        $clean = trim((string) $url);

        return str_starts_with(strtolower($clean), 'https://') ? $clean : null;
    }

    // يحوّل رقم الهاتف إلى أرقام دولية فقط (بلا +) لبناء روابط tel وwa.me، أو null إن لم يصلح.
    // يعتمد على PhoneNumber نفسه الذي يستعمله الدفع (اليمن 967 افتراضيًا).
    // لا يبني رابطًا من نص غير رقمي أبدًا.
    private function digits(?string $phone): ?string
    {
        $normalized = PhoneNumber::normalize($phone);

        return $normalized === null ? null : PhoneNumber::digits($normalized);
    }
}
