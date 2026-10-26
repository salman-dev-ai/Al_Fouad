<?php

namespace App\View\Components\Store;

use App\Models\StoreSetting;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Footer extends Component
{
    public ?StoreSetting $settings;

    public function __construct()
    {
        $this->settings = StoreSetting::current();
    }

    public function telUrl(): ?string
    {
        $phone = preg_replace('/[^\d+]/', '', (string) $this->settings?->phone);

        return $phone !== '' ? "tel:{$phone}" : null;
    }

    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->settings?->whatsapp_phone);

        return $digits !== '' ? "https://wa.me/{$digits}" : null;
    }

    /** @return array<string, string> اسم المنصة => الرابط */
    public function socialLinks(): array
    {
        $labels = [
            'facebook' => 'فيسبوك',
            'instagram' => 'إنستغرام',
            'tiktok' => 'تيك توك',
            'x' => 'إكس',
            'snapchat' => 'سناب شات',
            'telegram' => 'تيليجرام',
        ];

        $links = [];

        foreach ($labels as $key => $label) {
            $url = $this->settings?->social_links[$key] ?? null;

            // نعرض روابط https فقط، دفاعًا إضافيًا فوق التحقق في لوحة الإدارة
            if (is_string($url) && str_starts_with($url, 'https://')) {
                $links[$label] = $url;
            }
        }

        return $links;
    }

    /** @return array<int, array{day: string, text: string}> */
    public function hours(): array
    {
        return collect($this->settings?->opening_hours ?? [])
            ->map(fn (array $row): array => [
                'day' => $row['day'] ?? '',
                'text' => ($row['is_closed'] ?? false)
                    ? 'مغلق'
                    : substr((string) ($row['opens_at'] ?? ''), 0, 5) . ' – ' . substr((string) ($row['closes_at'] ?? ''), 0, 5),
            ])
            ->all();
    }

    public function render(): View|\Closure|string
    {
        return view('components.store.footer');
    }
}
