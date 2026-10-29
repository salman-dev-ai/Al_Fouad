<?php

use App\Models\ProductVariant;
use App\Models\StockAlert;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public int $variantId;

    #[Locked]
    public ?int $alertId = null;

    public string $phone = '';

    public bool $consent = false;

    // يهيّئ النموذج بمعرّف الخيار (مقفل فلا يغيّره المتصفح)، ويملأ الهاتف من حساب العميل إن كان مسجّلًا.
    // لا نقرأ شيئًا آخر من الطلب.
    // يُنشأ من صفحة المنتج حين يكون الخيار المحدد منتهيًا.
    public function mount(int $variantId): void
    {
        $this->variantId = $variantId;
        $this->phone = (string) auth()->user()?->phone;
    }

    // يسجّل طلب التنبيه بعد: تحديد المعدل، التحقق من الهاتف والموافقة، والتأكد أن الخيار ما زال منتهيًا.
    // تسجيل مكرر لنفس (الخيار + الرقم) يجدّد الطلب ولا يكرره، وللرقم حد 10 تنبيهات معلّقة.
    // الخطأ يظهر تحت الحقل، والنجاح يُظهر رسالة مع زر إلغاء.
    public function submit(): void
    {
        $key = 'stock-alert:' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('phone', 'محاولات كثيرة، حاول بعد قليل.');

            return;
        }

        RateLimiter::hit($key, 600);

        $this->validate(
            ['phone' => ['required', 'string', 'max:30'], 'consent' => ['accepted']],
            ['phone.required' => 'أدخل رقم الجوال.', 'consent.accepted' => 'يلزم الموافقة على التنبيه.'],
        );

        $phone = PhoneNumber::normalize($this->phone);

        if ($phone === null) {
            $this->addError('phone', 'أدخل رقم جوال صحيحًا.');

            return;
        }

        $variant = ProductVariant::query()->with('product.category')->find($this->variantId);

        abort_unless($variant && $variant->product->is_active && $variant->product->category->is_active, 404);

        if ($variant->stock_quantity > 0) {
            $this->addError('phone', 'هذا الخيار متوفر الآن، يمكنك إضافته إلى السلة.');

            return;
        }

        $pending = StockAlert::query()
            ->where('phone', $phone)
            ->whereNull('notified_at')
            ->where('product_variant_id', '!=', $variant->id)
            ->count();

        if ($pending >= StockAlert::MAX_PENDING_PER_PHONE) {
            $this->addError('phone', 'وصلت إلى الحد الأقصى من التنبيهات لهذا الرقم.');

            return;
        }

        $alert = StockAlert::query()->updateOrCreate(
            ['product_variant_id' => $variant->id, 'phone' => $phone],
            ['user_id' => auth()->id(), 'consent_at' => now(), 'available_at' => null, 'notified_at' => null],
        );

        $this->alertId = $alert->id;
        $this->consent = false;
    }

    // يلغي طلب التنبيه الذي سجّله هذا المكوّن نفسه (المعرّف مقفل، فلا يمكن إلغاء طلب غيره).
    // يحذف السجل نهائيًا، ويعيد النموذج لحالته الأولى.
    // مجرد ضغط مكرر لا يسبب خطأ.
    public function cancel(): void
    {
        if ($this->alertId !== null) {
            StockAlert::query()->whereKey($this->alertId)->delete();
            $this->alertId = null;
        }
    }
};
?>

{{-- نموذج «أبلغني عند التوفر»: يظهر فقط للخيار المنتهي، ويتحول بعد التسجيل لرسالة نجاح مع زر إلغاء. --}}
<div class="space-y-3 rounded-lg border border-line bg-surface p-4">
    @if ($alertId !== null)
        <p class="flex items-start gap-2 text-sm font-semibold text-brand-700" role="status">
            <x-lucide-bell-ring class="mt-0.5 size-5 shrink-0" aria-hidden="true" />
            <span>تم تسجيل طلبك، سنتواصل معك عبر واتساب مرة واحدة عند توفر هذا الخيار.</span>
        </p>
        <button type="button" wire:click="cancel" wire:loading.attr="disabled" wire:target="cancel"
                class="text-sm font-semibold text-ink-soft underline underline-offset-4 hover:text-ink">
            إلغاء التنبيه
        </button>
    @else
        <h2 class="flex items-center gap-2 text-sm font-extrabold">
            <x-lucide-bell class="size-5 text-brand-700" aria-hidden="true" />
            نفد هذا الخيار؟ أبلغني عند توفره
        </h2>

        <form wire:submit="submit" class="space-y-3" novalidate>
            <x-store.field name="phone" label="رقم الجوال (واتساب)" type="tel" dir="ltr"
                           placeholder="777123456" wire:model="phone" />

            <label class="flex items-start gap-2 text-sm">
                <input type="checkbox" wire:model="consent" class="mt-0.5 size-4 shrink-0 accent-brand-600">
                <span>أوافق أن يتواصل معي المتجر عبر واتساب <strong>مرة واحدة فقط</strong> عند توفر هذا الخيار، ولا أريد أي رسائل أخرى.</span>
            </label>
            @error('consent') <p class="text-sm font-semibold text-danger" role="alert">{{ $message }}</p> @enderror

            <button type="submit" wire:loading.attr="disabled" wire:target="submit"
                    class="rounded-md bg-brand-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-700 disabled:opacity-60">
                <span wire:loading.remove wire:target="submit">أبلغني</span>
                <span wire:loading wire:target="submit">جارٍ التسجيل…</span>
            </button>
        </form>
    @endif
</div>
