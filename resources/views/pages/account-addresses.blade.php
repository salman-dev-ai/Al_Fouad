<?php

use App\Livewire\Forms\AddressForm;
use App\Models\Address;
use App\Support\GoogleMapsUrl;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('عناويني')] class extends Component {
    public AddressForm $form;

    public bool $showForm = false;

    public string $status = '';

    // عناوين المستخدم الحالي فقط، الافتراضي أولًا ثم الأحدث.
    // مصدر الحقيقة الوحيد للقائمة، ويُمسح كاشه بعد كل تعديل (unset).
    // لا يقبل أي معرّف مستخدم من المتصفح.
    #[Computed]
    public function addresses(): Collection
    {
        return auth()->user()->addresses()->orderByDesc('is_default')->orderByDesc('id')->get();
    }

    // يفتح نموذج عنوان جديد ويملأ الاسم والجوال مسبقًا من الحساب لتوفير الكتابة.
    // يمسح أي أخطاء أو بيانات تعديل سابقة.
    // ينقل التركيز لبداية النموذج عبر العرض (autofocus).
    public function create(): void
    {
        $this->form->reset();
        $this->resetValidation();

        $user = auth()->user();
        $this->form->recipient_name = $user->name;
        $this->form->phone = (string) $user->phone;

        $this->status = '';
        $this->showForm = true;
    }

    // يفتح نموذج التعديل لعنوان يملكه المستخدم (findOrFail على علاقته، فمعرّف غيره يعطي 404).
    // يملأ النموذج ببياناته.
    // لا يغيّر شيئًا في قاعدة البيانات حتى الحفظ.
    public function edit(int $id): void
    {
        $address = auth()->user()->addresses()->findOrFail($id);

        $this->resetValidation();
        $this->form->setAddress($address);

        $this->status = '';
        $this->showForm = true;
    }

    // يحفظ العنوان (جديدًا أو معدّلًا) بعد التحقق، مع حدّ أقصى لعدد العناوين.
    // الحفظ والتحقق في كلاس AddressForm، وهنا فقط التنسيق وإغلاق النموذج.
    // يمسح كاش القائمة لتظهر التغييرات فورًا.
    public function save(): void
    {
        $user = auth()->user();

        if (! $this->form->addressId && $user->addresses()->count() >= Address::MAX_PER_USER) {
            $this->addError('limit', 'وصلت للحد الأقصى (' . Address::MAX_PER_USER . ' عناوين). احذف عنوانًا قديمًا أولًا.');

            return;
        }

        $this->form->save($user);

        $this->form->reset();
        $this->showForm = false;
        $this->status = 'تم حفظ العنوان.';

        unset($this->addresses);
    }

    // يغلق النموذج دون حفظ ويمسح الأخطاء.
    public function cancel(): void
    {
        $this->form->reset();
        $this->resetValidation();
        $this->showForm = false;
    }

    // يجعل عنوانًا يملكه المستخدم هو الافتراضي (الدالة داخل نموذج Address وتعمل في معاملة).
    // معرّف لا يخصه يعطي 404.
    // يحدّث القائمة فورًا.
    public function makeDefault(int $id): void
    {
        auth()->user()->addresses()->findOrFail($id)->makeDefault();

        $this->status = 'تم تعيين العنوان الافتراضي.';
        unset($this->addresses);
    }

    // يحذف عنوانًا يملكه المستخدم، وإن كان افتراضيًا يرقّي أحدث عنوان متبقٍ ليصبح الافتراضي.
    // كله داخل معاملة مع قفل الصف لتفادي التعارض.
    // إن كان المحذوف مفتوحًا للتعديل يُغلق النموذج.
    public function delete(int $id): void
    {
        $user = auth()->user();

        DB::transaction(function () use ($user, $id): void {
            $address = $user->addresses()->lockForUpdate()->findOrFail($id);
            $wasDefault = $address->is_default;

            $address->delete();

            if ($wasDefault) {
                $user->addresses()->latest('id')->first()?->makeDefault();
            }
        });

        if ($this->form->addressId === $id) {
            $this->cancel();
        }

        $this->status = 'تم حذف العنوان.';
        unset($this->addresses);
    }
};
?>

{{-- صفحة عناوين الشحن: قائمة العناوين مع إضافة وتعديل وحذف وتعيين افتراضي. --}}
{{-- النموذج يظهر فوق القائمة عند الحاجة، وكل الحالة (فتح/إغلاق) في Livewire لأنها مرتبطة بالبيانات. --}}
<x-store.account-shell heading="عناويني">
    <div class="space-y-6">

        @if ($status)
            <p class="flex items-center gap-1.5 text-sm font-bold text-brand-700" role="status">
                <x-lucide-circle-check class="size-4" aria-hidden="true" /> {{ $status }}
            </p>
        @endif

        @error('limit')
            <p class="rounded-md border border-danger/30 px-3 py-2 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
        @enderror

        @if ($showForm)
            <form wire:submit="save" novalidate class="space-y-5 rounded-lg border border-brand-600 p-5"
                  aria-labelledby="address-form-heading">
                <h2 id="address-form-heading" class="text-lg font-extrabold">
                    {{ $form->addressId ? 'تعديل العنوان' : 'عنوان جديد' }}
                </h2>

                <x-store.field name="form.label" label="اسم العنوان" placeholder="المنزل، العمل…" wire:model="form.label" />

                <x-store.field name="form.recipient_name" label="اسم المستلم" required autocomplete="name"
                               wire:model="form.recipient_name" />

                <x-store.field name="form.phone" label="رقم جوال المستلم" required type="tel" inputmode="tel" dir="ltr"
                               placeholder="777123456" autocomplete="tel" wire:model="form.phone" />

                <x-store.field name="form.city" label="المدينة" required autocomplete="address-level2" wire:model="form.city" />

                <x-store.field name="form.address_line" as="textarea" label="العنوان بالتفصيل" required
                               placeholder="الحي، الشارع، أقرب معلم..." wire:model="form.address_line" />

                <x-store.field name="form.notes" as="textarea" label="ملاحظات للتوصيل" wire:model="form.notes" />

                <x-store.location-field model="form.location_url" />

                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="form.is_default" class="size-4 rounded border-line accent-brand-600">
                    اجعله عنواني الافتراضي
                </label>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" wire:loading.attr="disabled" wire:target="save"
                            class="rounded-md bg-brand-600 px-6 py-3 font-extrabold text-white hover:bg-brand-700 disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">حفظ العنوان</span>
                        <span wire:loading wire:target="save">جارٍ الحفظ…</span>
                    </button>
                    <button type="button" wire:click="cancel"
                            class="rounded-md border border-line px-5 py-3 font-bold hover:bg-surface">إلغاء</button>
                </div>
            </form>
        @else
            <button type="button" wire:click="create"
                    class="inline-flex items-center gap-2 rounded-md bg-brand-600 px-5 py-3 font-extrabold text-white hover:bg-brand-700">
                <x-lucide-plus class="size-5" aria-hidden="true" />
                إضافة عنوان جديد
            </button>
        @endif

        <ul class="space-y-4">
            @forelse ($this->addresses as $address)
                <li wire:key="address-{{ $address->id }}"
                    class="rounded-lg border p-5 {{ $address->is_default ? 'border-brand-600' : 'border-line' }}">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-extrabold">{{ $address->label ?: 'عنوان' }}</span>
                        @if ($address->is_default)
                            <span class="rounded-sm bg-brand-700 px-2 py-0.5 text-xs font-bold text-white">افتراضي</span>
                        @endif
                    </div>

                    <dl class="mt-3 space-y-1 text-sm">
                        <div class="flex gap-2"><dt class="w-24 shrink-0 text-ink-soft">المستلم</dt><dd>{{ $address->recipient_name }}</dd></div>
                        <div class="flex gap-2"><dt class="w-24 shrink-0 text-ink-soft">الجوال</dt><dd dir="ltr">{{ $address->phone }}</dd></div>
                        <div class="flex gap-2"><dt class="w-24 shrink-0 text-ink-soft">المدينة</dt><dd>{{ $address->city }}</dd></div>
                        <div class="flex gap-2"><dt class="w-24 shrink-0 text-ink-soft">العنوان</dt><dd>{{ $address->address_line }}</dd></div>
                        @if ($address->notes)
                            <div class="flex gap-2"><dt class="w-24 shrink-0 text-ink-soft">ملاحظات</dt><dd>{{ $address->notes }}</dd></div>
                        @endif
                        @if ($address->location_url && \App\Support\GoogleMapsUrl::isValid($address->location_url))
                            <div class="flex gap-2">
                                <dt class="w-24 shrink-0 text-ink-soft">الموقع</dt>
                                <dd><a href="{{ $address->location_url }}" target="_blank" rel="noopener noreferrer"
                                       class="font-semibold text-brand-700 underline underline-offset-4">عرض على الخريطة</a></dd>
                            </div>
                        @endif
                    </dl>

                    <div class="mt-4 flex flex-wrap gap-2 text-sm">
                        <button type="button" wire:click="edit({{ $address->id }})"
                                class="rounded-md border border-line px-4 py-2 font-bold hover:bg-surface">تعديل</button>

                        @unless ($address->is_default)
                            <button type="button" wire:click="makeDefault({{ $address->id }})"
                                    class="rounded-md border border-line px-4 py-2 font-bold hover:bg-surface">اجعله افتراضيًا</button>
                        @endunless

                        <button type="button" wire:click="delete({{ $address->id }})"
                                wire:confirm="هل تريد حذف هذا العنوان؟"
                                class="rounded-md border border-danger/40 px-4 py-2 font-bold text-danger hover:bg-surface">حذف</button>
                    </div>
                </li>
            @empty
                <li class="rounded-lg border border-dashed border-line p-8 text-center text-ink-soft">
                    لم تحفظ أي عنوان بعد. أضف عنوانك لتُكمل طلباتك أسرع.
                </li>
            @endforelse
        </ul>
    </div>
</x-store.account-shell>