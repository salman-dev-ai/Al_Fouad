<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('كلمة المرور')] class extends Component {
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $saved = false;

    // يغيّر كلمة مرور المستخدم الحالي بعد التحقق من كلمته الحالية.
    // محاولات الكلمة الحالية الخاطئة محدودة (5 كل 15 دقيقة) لمنع تخمينها من جلسة مفتوحة على جهاز مسروق.
    // بعد النجاح تتجدد الجلسة وتُفرَّغ الحقول.
    public function save(): void
    {
        $key = 'change-password:' . auth()->id();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('current_password', 'محاولات كثيرة. حاول بعد قليل.');

            return;
        }

        $this->validate(
            [
                'current_password' => ['required', 'string', 'max:255'],
                'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)],
            ],
            [
                'current_password.required' => 'أدخل كلمة المرور الحالية.',
                'password.required' => 'أدخل كلمة المرور الجديدة.',
                'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
                'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
                'password.different' => 'يجب أن تختلف كلمة المرور الجديدة عن الحالية.',
            ],
        );

        $user = auth()->user();

        if (! Hash::check($this->current_password, $user->password)) {
            RateLimiter::hit($key, 900);
            $this->addError('current_password', 'كلمة المرور الحالية غير صحيحة.');

            return;
        }

        RateLimiter::clear($key);

        $user->update(['password' => $this->password]);
        session()->regenerate();

        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->saved = true;
    }

    // يخفي رسالة النجاح عند أي تعديل جديد في الحقول.
    public function updated(string $property): void
    {
        $this->saved = false;
    }
};
?>

<x-store.account-shell heading="كلمة المرور">
    <form wire:submit="save" novalidate class="max-w-lg space-y-5 rounded-lg border border-line p-6">
        <x-store.field name="current_password" label="كلمة المرور الحالية" required type="password" dir="ltr"
                       autocomplete="current-password" wire:model="current_password" />

        <x-store.field name="password" label="كلمة المرور الجديدة" required type="password" dir="ltr"
                       autocomplete="new-password" helper="8 أحرف على الأقل." wire:model="password" />

        <x-store.field name="password_confirmation" label="تأكيد كلمة المرور الجديدة" required type="password" dir="ltr"
                       autocomplete="new-password" wire:model="password_confirmation" />

        <div class="flex items-center gap-4">
            <button type="submit" wire:loading.attr="disabled" wire:target="save"
                    class="rounded-md bg-brand-600 px-6 py-3 font-extrabold text-white hover:bg-brand-700 disabled:opacity-60">
                <span wire:loading.remove wire:target="save">تغيير كلمة المرور</span>
                <span wire:loading wire:target="save">جارٍ الحفظ…</span>
            </button>

            @if ($saved)
                <p class="flex items-center gap-1.5 text-sm font-bold text-brand-700" role="status">
                    <x-lucide-circle-check class="size-4" aria-hidden="true" /> تم تغيير كلمة المرور
                </p>
            @endif
        </div>
    </form>
</x-store.account-shell>