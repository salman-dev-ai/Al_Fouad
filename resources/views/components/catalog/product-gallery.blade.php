        {{-- ========== المعرض: كتلة لكل لون، والتبديل فوري في المتصفح ========== --}}
        <div
            x-data="{
                active: 0,
                x0: 0,
                init() {
                    // عند تغيير اللون نعود لأول صورة (وهي الرئيسية)
                    this.$wire.$watch('color', () => { this.active = 0; });

                    // تحميل مسبق لأول صورة من كل لون بعد خمول الصفحة ليكون التبديل لحظيًا
                    const urls = @js($firstUrls);
                    const preload = () => urls.forEach((u) => { new Image().src = u; });
                    'requestIdleCallback' in window ? requestIdleCallback(preload) : setTimeout(preload, 1500);
                },
            }"
        >
            @foreach ($galleries as $gallery)
                @php
                    $gImages = $gallery['images'];
                    $gCount = count($gImages);
                    $isCurrent = $gallery['name'] === $color;
                @endphp

                <div wire:key="gallery-{{ $loop->index }}"
                     x-show="$wire.color === @js($gallery['name'])"
                     @unless ($isCurrent) x-cloak @endunless
                     @if ($gCount > 1)
                         @keydown.left.prevent="active = (active + 1) % {{ $gCount }}"
                         @keydown.right.prevent="active = (active + {{ $gCount - 1 }}) % {{ $gCount }}"
                     @endif
                     class="flex flex-col gap-3 lg:flex-row">

                    {{-- الصورة الكبيرة (الأولى في الكود = يمين في RTL على الشاشات الكبيرة بعد المصغرات) --}}
                    <div class="relative order-1 aspect-4/5 flex-1 overflow-hidden rounded-2xl bg-brand-50 lg:order-2"
                         @if ($gCount > 1)
                             @touchstart.passive="x0 = $event.changedTouches[0].clientX"
                             @touchend="const d = $event.changedTouches[0].clientX - x0; if (Math.abs(d) > 50) { active = (active + (d > 0 ? {{ $gCount - 1 }} : 1)) % {{ $gCount }}; }"
                         @endif>
                        @forelse ($gImages as $i => $image)
                            <img x-show="active === {{ $i }}"
                                 @if ($i > 0) x-cloak @endif
                                 @if ($i > 0 || ! $isCurrent) loading="lazy" @else fetchpriority="high" @endif
                                 src="{{ $image['url'] }}" alt="{{ $image['alt'] ?: $product->name . ' - ' . $gallery['name'] }}"
                                 decoding="async" class="absolute inset-0 size-full object-cover">
                        @empty
                            <span class="flex size-full items-center justify-center text-brand-500/50" aria-hidden="true">
                                <x-lucide-shirt class="size-24" />
                            </span>
                        @endforelse

                        @if ($hasDiscount && $isCurrent)
                            <span class="absolute start-3 top-3 rounded-full bg-danger px-3 py-1 text-xs font-extrabold text-white shadow">خصم {{ $discountPercent }}%</span>
                        @endif

                        @if ($gCount > 1)
                            <button type="button" @click="active = (active + {{ $gCount - 1 }}) % {{ $gCount }}"
                                    aria-label="الصورة السابقة"
                                    class="absolute right-3 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/95 text-ink shadow hover:bg-white">
                                <x-lucide-chevron-right class="size-5" aria-hidden="true" />
                            </button>
                            <button type="button" @click="active = (active + 1) % {{ $gCount }}"
                                    aria-label="الصورة التالية"
                                    class="absolute left-3 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/95 text-ink shadow hover:bg-white">
                                <x-lucide-chevron-left class="size-5" aria-hidden="true" />
                            </button>

                            {{-- نقاط (للجوال خصوصًا) --}}
                            <div class="absolute inset-x-0 bottom-3 flex justify-center gap-1.5 lg:hidden" aria-hidden="true">
                                @foreach ($gImages as $i => $image)
                                    <span class="size-2 rounded-full transition" :class="active === {{ $i }} ? 'bg-white' : 'bg-white/50'"></span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- المصغرات: عمود جانبي على الشاشات الكبيرة، وصف أسفل الصورة على الجوال --}}
                    @if ($gCount > 1)
                        <ul class="order-2 flex gap-2 overflow-x-auto pb-1 lg:order-1 lg:max-h-[34rem] lg:w-20 lg:flex-col lg:overflow-y-auto lg:overflow-x-hidden lg:pb-0 [scrollbar-width:none]">
                            @foreach ($gImages as $i => $image)
                                <li class="w-16 shrink-0 lg:w-full">
                                    <button type="button" @click="active = {{ $i }}"
                                            :aria-current="active === {{ $i }}" aria-label="عرض الصورة {{ $i + 1 }}"
                                            :class="active === {{ $i }} ? 'border-brand-600' : 'border-transparent opacity-80 hover:opacity-100'"
                                            class="block aspect-square w-full overflow-hidden rounded-xl border-2 transition">
                                        <img src="{{ $image['url'] }}" alt="" width="80" height="80" loading="lazy" decoding="async" class="size-full object-cover">
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
