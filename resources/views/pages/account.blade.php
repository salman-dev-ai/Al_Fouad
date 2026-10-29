<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('حسابي')] class extends Component {};
?>

{{-- صفحة الحساب الرئيسية: ترحيب وبطاقات وصول سريع. --}}
{{-- تُضاف بطاقات الأقسام الجديدة (العناوين، الطلبات، المفضلة) في الخطوات القادمة. --}}
<x-store.account-shell heading="أهلًا، {{ auth()->user()->name }}">
    <ul class="grid gap-4 sm:grid-cols-2">
        <li>
            <a href="{{ route('account.profile') }}" wire:navigate
               class="block rounded-lg border border-line p-5 hover:border-brand-500">
                <span class="block font-extrabold">بياناتي</span>
                <span class="text-sm text-ink-soft">الاسم والبريد الإلكتروني</span>
            </a>
        </li>
        <li>
    <a href="{{ route('account.addresses') }}" wire:navigate
       class="block rounded-lg border border-line p-5 hover:border-brand-500">
        <span class="block font-extrabold">عناويني</span>
        <span class="text-sm text-ink-soft">عناوين الشحن المحفوظة</span>
    </a>
</li>
        <li>
            <a href="{{ route('account.password') }}" wire:navigate
               class="block rounded-lg border border-line p-5 hover:border-brand-500">
                <span class="block font-extrabold">كلمة المرور</span>
                <span class="text-sm text-ink-soft">غيّر كلمة مرور حسابك</span>
            </a>
        </li>
    </ul>
</x-store.account-shell>