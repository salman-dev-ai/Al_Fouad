<?php

use App\Models\Product;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    private const MAX_FAVORITES = 200;

    // معرّفات مفضلة المستخدم الحالي لعرضها فقط (مقفلة، فلا يعدّلها المتصفح؛ ولا يعتمد عليها الخادم أصلًا).
    #[Locked]
    public array $ids = [];

    // يحمّل مفضلة المستخدم عند فتح الصفحة (قائمة فارغة للزائر، فمفضلته في متصفحه).
    public function mount(): void
    {
        $this->ids = $this->currentIds();
    }

    // يقرأ معرّفات المفضلة من قاعدة البيانات للمستخدم المسجّل فقط.
    // مصدر الحقيقة الوحيد، ويُستدعى بعد كل تعديل.
    private function currentIds(): array
    {
        if (! auth()->check()) {
            return [];
        }

        return auth()->user()->favoriteProducts()->pluck('products.id')->map(fn ($id) => (int) $id)->all();
    }

    // يضيف منتجًا للمفضلة أو يزيله (دالة «اضبط الحالة» وليست «بدّل»، فتكرار الطلب آمن).
    // يقبل فقط منتجات معروضة فعلًا (مفعّلة وتصنيفها مفعّل)، وبحدّ أقصى 200 منتج، وبحدّ معدل لكل مستخدم.
    // يعيد حساب القائمة من قاعدة البيانات فتتحدث الواجهة بالنتيجة الحقيقية.
    public function setFavorite(int $productId, bool $on): void
    {
        $user = auth()->user();

        if (! $user || RateLimiter::tooManyAttempts('favorites:' . $user->id, 120)) {
            return;
        }

        RateLimiter::hit('favorites:' . $user->id, 60);

        if ($on) {
            $exists = Product::query()
                ->active()
                ->whereHas('category', fn ($q) => $q->active())
                ->whereKey($productId)
                ->exists();

            if ($exists && $user->favoriteProducts()->count() < self::MAX_FAVORITES) {
                $user->favoriteProducts()->syncWithoutDetaching([$productId]);
            }
        } else {
            $user->favoriteProducts()->detach($productId);
        }

        $this->ids = $this->currentIds();
    }

    // يدمج مفضلة الزائر المحلية في حساب المستخدم بعد دخوله (دون تكرار بفضل syncWithoutDetaching).
    // المدخلات غير موثوقة: تُنظَّف لأعداد صحيحة فريدة (حتى 100)، ويُقبل منها المعروض فعلًا فقط، ضمن حدّ 200.
    // لا يحذف شيئًا من مفضلة الحساب الموجودة.
    public function merge(array $ids): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $clean = collect($ids)
            ->filter(fn ($id) => is_int($id) || (is_string($id) && ctype_digit($id)))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take(100)
            ->values();

        $room = max(0, self::MAX_FAVORITES - $user->favoriteProducts()->count());

        $valid = Product::query()
            ->active()
            ->whereHas('category', fn ($q) => $q->active())
            ->whereIn('id', $clean)
            ->pluck('id')
            ->take($room)
            ->all();

        $user->favoriteProducts()->syncWithoutDetaching($valid);

        $this->ids = $this->currentIds();
    }
};
?>

{{-- عنصر غير مرئي: يهيّئ وحدة المفضلة في المتصفح بحالة الخادم، ويراقب أي تغيير لقائمة المعرّفات لينقله للواجهة. --}}
{{-- لا يرسم شيئًا، ويوضع مرة واحدة في التخطيط. --}}
<div class="hidden" aria-hidden="true" x-data
     x-init="window.Favorites?.boot({ authed: @js(auth()->check()), ids: [...$wire.ids], wire: $wire });
              $wire.$watch('ids', (value) => window.Favorites?.sync(value))"></div>