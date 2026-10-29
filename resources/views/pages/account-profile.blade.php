<?php

use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('بياناتي')] class extends Component {
    public string $name = '';

    public string $email = '';

    public bool $saved = false;

    // يملأ النموذج ببيانات الحساب الحالي عند فتح الصفحة.
    // البيانات تُقرأ من المستخدم المسجّل دخوله فقط، لا من أي معرّف يرسله المتصفح.
    // الجوال لا يُعرض للتعديل لأنه معرّف الدخول.
    public function mount(): void
    {
        $user = auth()->user();

        $this->name = $user->name;
        $this->email = (string) $user->email;
    }

    // يتحقق ثم يحفظ الاسم والبريد للمستخدم الحالي فقط (تفرّد البريد يتجاهل سجله هو).
    // لا يُمَس الجوال ولا صلاحية المدير (is_admin) من هذه الدالة إطلاقًا.
    // يعرض رسالة نجاح تختفي عند أي تعديل جديد.
    public function save(): void
    {
        $user = auth()->user();

        $validated = $this->validate(
            [
                'name' => ['required', 'string', 'min:3', 'max:100'],
                'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            ],
            [
                'name.required' => 'الاسم مطلوب.',
                'name.min' => 'الاسم قصير جدًا.',
                'email.email' => 'أدخل بريدًا إلكترونيًا صحيحًا.',
                'email.unique' => 'هذا البريد مستخدم في حساب آخر.',
            ],
        );

        $user->update([
            'name' => trim($validated['name']),
            'email' => filled($validated['email']) ? mb_strtolower(trim($validated['email'])) : null,
        ]);

        $this->saved = true;
    }

    // يخفي رسالة «تم الحفظ» بمجرد أن يعدّل العميل أي حقل.
    public function updated(string $property): void
    {
        $this->saved = false;
    }
};
?>

<x-store.account-shell heading="بياناتي">
    <form wire:submit="save" novalidate class="max-w-lg space-y-5 rounded-lg border border-line p-6">
        <x-store.field name="name" label="الاسم الكامل" required autocomplete="name" wire:model="name" />

        <x-store.field name="email" label="البريد الإلكتروني" type="email" dir="ltr"
                       autocomplete="email" wire:model="email" />

        <div>
            <p class="mb-1.5 text-sm font-bold">رقم الجوال</p>
            <p class="rounded-md bg-surface px-3 py-2.5 text-sm" dir="ltr">{{ auth()->user()->phone }}</p>
            <p class="mt-1 text-xs text-ink-soft">رقم الجوال هو معرّف دخولك. لتغييره تواصل معنا عبر واتساب.</p>
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" wire:loading.attr="disabled" wire:target="save"
                    class="rounded-md bg-brand-600 px-6 py-3 font-extrabold text-white hover:bg-brand-700 disabled:opacity-60">
                <span wire:loading.remove wire:target="save">حفظ التغييرات</span>
                <span wire:loading wire:target="save">جارٍ الحفظ…</span>
            </button>

            @if ($saved)
                <p class="flex items-center gap-1.5 text-sm font-bold text-brand-700" role="status">
                    <x-lucide-circle-check class="size-4" aria-hidden="true" /> تم الحفظ
                </p>
            @endif
        </div>
    </form>
</x-store.account-shell>