<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('الرئيسية')] class extends Component {
};
?>

<div class="mx-auto max-w-7xl px-4 py-20 text-center sm:px-6 lg:px-8">
    <h1 class="text-3xl font-extrabold text-brand-700 sm:text-4xl">أهلًا بك في {{ config('app.name') }}</h1>
    <p class="mt-4 text-ink-soft">صفحة مؤقتة للتأكد من القالب، وسنستبدلها بالصفحة الرئيسية الحقيقية في الخطوة القادمة.</p>
</div>
