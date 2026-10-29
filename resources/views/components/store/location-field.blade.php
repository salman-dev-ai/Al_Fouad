{{-- حقل «موقعك على الخريطة»: زر لتحديد الموقع الحالي + حقل لرابط خرائط Google. --}}
{{-- Alpine يتولى التفاعل المحلي فقط (طلب الإذن وبناء الرابط)، ويكتب الناتج في خاصية Livewire. --}}
{{-- الخادم هو من يتحقق من الرابط في النهاية، فلا يُعتمد على المتصفح. --}}
@props(['model'])

<div class="space-y-3"
     x-data="{
        locating: false,
        message: '',
        locate() {
            if (!navigator.geolocation) { this.message = 'متصفحك لا يدعم تحديد الموقع.'; return; }
            this.locating = true;
            this.message = '';
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    $wire.$set(@js($model), 'https://www.google.com/maps?q=' + pos.coords.latitude.toFixed(6) + ',' + pos.coords.longitude.toFixed(6));
                    this.locating = false;
                    this.message = 'تم تحديد موقعك. يمكنك تعديل الرابط أو حذفه.';
                },
                () => {
                    this.locating = false;
                    this.message = 'تعذّر تحديد موقعك. اسمح للمتصفح بالوصول للموقع أو الصق رابطًا يدويًا.';
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }
     }">
    <button type="button" @click="locate()" :disabled="locating"
            class="flex items-center gap-2 rounded-md border border-brand-600 px-4 py-2.5 text-sm font-bold text-brand-700 hover:bg-brand-50 disabled:opacity-60">
        <x-lucide-map-pin class="size-4" aria-hidden="true" />
        <span x-text="locating ? 'جارٍ تحديد الموقع…' : 'استخدم موقعي الحالي'">استخدم موقعي الحالي</span>
    </button>
    <p x-show="message" x-cloak x-text="message" class="text-sm text-ink-soft" role="status"></p>

    <x-store.field :name="$model" label="رابط الموقع" type="url" dir="ltr"
                   placeholder="https://maps.app.goo.gl/..."
                   helper="يمكنك لصق رابط من تطبيق خرائط Google."
                   wire:model.blur="{{ $model }}" />
</div>