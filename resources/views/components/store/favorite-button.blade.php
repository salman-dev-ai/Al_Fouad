{{-- زر القلب: حالته محلية في Alpine (مفضّل أو لا)، ويقرأ ويكتب عبر window.Favorites. --}}
{{-- يتحدث تلقائيًا عند أي تغيير (من بطاقة أخرى أو من الخادم) بالحدث favorites-changed. --}}
{{-- ملاحظة: على وسوم المكوّنات نستعمل x-bind لا «:» لأن Blade يفسّر «:» على أنه تعبير PHP. --}}
@props(['product'])

<button type="button"
        x-data="{ on: window.Favorites?.has({{ $product->id }}) ?? false }"
        @favorites-changed.window="on = window.Favorites.has({{ $product->id }})"
        @click.prevent.stop="window.Favorites?.toggle({{ $product->id }})"
        x-bind:aria-pressed="on"
        x-bind:aria-label="on ? @js('إزالة من المفضلة: ' . $product->name) : @js('إضافة إلى المفضلة: ' . $product->name)"
        {{ $attributes->class(['grid size-10 place-items-center rounded-full bg-white/90 text-ink shadow hover:bg-white']) }}>
    <x-lucide-heart x-show="!on" class="size-5" aria-hidden="true" />
    <x-lucide-heart x-show="on" x-cloak class="size-5 fill-red-600 text-red-600" aria-hidden="true" />
</button>