{{-- شريط الإعلان المتحرك: يظهر فقط إذا فعّل المدير الإعلان وكان نصّه غير فارغ. --}}
{{-- النص من store_settings.announcement_text و$settings تُمرَّر من صنف المكوّن. --}}
{{-- الحركة بـ CSS فقط (بلا JS)، ونسختا المحتوى تضمنان حلقة سلسة، والنسخة الثانية مخفية عن قارئ الشاشة. --}}
@if ($settings?->announcement_enabled && filled($settings->announcement_text))
    @php
        $text = trim((string) $settings->announcement_text);
        // السرعة تتناسب مع طول النص (بين 25 و60 ثانية للدورة الكاملة) ليبقى إيقاع القراءة مريحًا.
        $duration = max(25, min(60, mb_strlen($text) * 0.6));
    @endphp

    <div class="announce relative overflow-hidden bg-brand-600 text-white" role="region" aria-label="إعلان المتجر">
        <div class="announce-mask py-2 text-sm font-semibold">
            <div class="announce-track flex w-max" style="--announce-duration: {{ $duration }}s">
                @foreach ([false, true] as $isCopy)
                    <div class="announce-group flex min-w-full shrink-0 items-center justify-around gap-10 px-5"
                         @if ($isCopy) aria-hidden="true" @endif>
                        @foreach (range(1, 4) as $n)
                            <span class="flex items-center gap-10 whitespace-nowrap"
                                  @if (! $isCopy && $n > 1) aria-hidden="true" @endif>
                                <span>{{ $text }}</span>
                                <span class="text-white/50" aria-hidden="true">✦</span>
                            </span>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
