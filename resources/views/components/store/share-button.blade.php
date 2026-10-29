{{-- زر المشاركة: يفتح نافذة المشاركة الأصلية في الجهاز إن وُجدت، وإلا ينسخ الرابط للحافظة. --}}
{{-- الرابط عام وصحيح (من route() في الخادم) ويحمل اللون المحدد حاليًا (?color=) إن وُجد. --}}
{{-- إلغاء المستخدم لنافذة المشاركة لا يُعدّ خطأ ولا يُنسخ بعده شيء. --}}
@props(['url', 'title'])

<div class="relative"
     x-data="{
        copied: false,
        async share() {
            const link = new URL(@js($url));
            if ($wire?.color) { link.searchParams.set('color', $wire.color); }

            const data = { title: @js($title), url: link.toString() };

            try {
                if (navigator.share && (!navigator.canShare || navigator.canShare(data))) {
                    await navigator.share(data);
                    return;
                }
            } catch (e) {
                if (e.name === 'AbortError') { return; }
            }

            try {
                await navigator.clipboard.writeText(data.url);
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2500);
            } catch (e) {
                window.prompt('انسخ الرابط:', data.url);
            }
        }
     }">
    <button type="button" @click="share()" aria-label="مشاركة المنتج"
            {{ $attributes->class(['grid size-10 place-items-center rounded-full bg-white/90 text-ink shadow hover:bg-white']) }}>
        <x-lucide-share-2 class="size-5" aria-hidden="true" />
    </button>

    <p x-show="copied" x-cloak role="status"
       class="absolute end-0 top-12 z-20 whitespace-nowrap rounded-md bg-ink px-3 py-1.5 text-xs font-bold text-white">
        تم نسخ الرابط
    </p>
</div>