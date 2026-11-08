    {{-- ========== الهيرو: نص وتصنيفات + صورة مقوّسة متحركة ========== --}}
    <section
        class="relative isolate overflow-hidden"
        aria-roledescription="شريط عروض متحرك"
        aria-label="العروض"
        x-data="{
            i: 0,
            n: {{ max(1, $count) }},
            p: 0,
            hover: false,
            paused: false,
            x0: 0,
            timer: null,
            init() {
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { this.paused = true; }
                if (this.n > 1) {
                    this.timer = setInterval(() => {
                        if (this.hover || this.paused) return;
                        this.p += 1;
                        if (this.p >= 100) this.next();
                    }, 60);
                }
            },
            destroy() { clearInterval(this.timer); },
            go(k) { this.i = (k + this.n) % this.n; this.p = 0; },
            next() { this.go(this.i + 1); },
            prev() { this.go(this.i - 1); },
        }"
        @mouseenter="hover = true" @mouseleave="hover = false"
        @focusin="hover = true" @focusout="hover = false"
        @touchstart.passive="x0 = $event.changedTouches[0].clientX"
        @touchend="const d = $event.changedTouches[0].clientX - x0; if (Math.abs(d) > 50) { d > 0 ? prev() : next(); }"
        @keydown.left.prevent="next()" @keydown.right.prevent="prev()"
    >
        {{-- زخارف خلفية --}}
        <div class="absolute -end-24 top-8 -z-10 size-72 rounded-full bg-[#efe7f3] sm:size-96" aria-hidden="true"></div>
        <x-lucide-heart class="absolute end-[48%] top-14 -z-10 hidden size-16 -rotate-12 text-[#fbd9cf] lg:block" aria-hidden="true" />

        <div class="mx-auto grid max-w-7xl items-center gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[1.05fr_1fr] lg:gap-10 lg:px-8 lg:py-12">

            {{-- ===== العمود النصي (يمين في RTL) ===== --}}
            <div class="order-2 lg:order-1">
                @if ($count > 0)
                    <div class="grid" :aria-live="paused ? 'polite' : 'off'">
                        @foreach ($ads as $ad)
                            <div class="col-start-1 row-start-1 transition-opacity duration-500"
                                 :class="i === {{ $loop->index }} ? 'opacity-100' : 'pointer-events-none opacity-0'"
                                 :inert="i !== {{ $loop->index }}"
                                 :aria-hidden="i !== {{ $loop->index }} ? 'true' : 'false'"
                                 role="group" aria-roledescription="شريحة"
                                 aria-label="{{ $loop->iteration }} من {{ $count }}">
                                <h2 class="text-4xl font-extrabold leading-tight text-brand-700 sm:text-5xl lg:text-6xl">{{ $ad['title'] }}</h2>

                                @if ($ad['description'])
                                    <p class="mt-4 text-lg leading-relaxed text-[#a8946f] sm:text-xl">{{ $ad['description'] }}</p>
                                @endif

                                <a href="{{ $ad['href'] ?? route('products.index') }}"
                                   @if ($ad['href'] === null || $ad['internal']) wire:navigate @else rel="noopener noreferrer" @endif
                                   class="mt-7 inline-flex items-center gap-3 rounded-full bg-brand-600 px-8 py-3.5 text-lg font-extrabold text-white shadow-sm transition hover:bg-brand-700">
                                    تسوّق الآن
                                    <x-lucide-chevron-left class="size-5" aria-hidden="true" />
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <h2 class="text-4xl font-extrabold leading-tight text-brand-700 sm:text-5xl lg:text-6xl">أهلًا بك في {{ config('app.name') }}</h2>
                    <p class="mt-4 text-lg text-[#a8946f] sm:text-xl">اختر مقاسك ولونك بسهولة، وادفع عند الاستلام.</p>
                    <a href="{{ route('products.index') }}" wire:navigate
                       class="mt-7 inline-flex items-center gap-3 rounded-full bg-brand-600 px-8 py-3.5 text-lg font-extrabold text-white shadow-sm transition hover:bg-brand-700">
                        تسوّق الآن
                        <x-lucide-chevron-left class="size-5" aria-hidden="true" />
                    </a>
                @endif

                {{-- التصنيفات تحت النص --}}
                @if ($heroCats->isNotEmpty())
                    <div class="mt-10">
                        <h2 class="text-xl font-extrabold">تسوّق حسب الفئة</h2>

                        <ul class="mt-4 grid grid-cols-3 gap-3 sm:gap-4">
                            @foreach ($heroCats as $category)
                                <li>
                                    <a href="{{ route('products.index', ['category' => $category->slug]) }}" wire:navigate
                                       class="group block overflow-hidden rounded-3xl {{ $pastels[$loop->index % 3] }}">
                                        <div class="aspect-3/4 overflow-hidden">
                                            @if ($category->image)
                                                <img src="{{ asset('storage/' . $category->image) }}" alt="" loading="lazy"
                                                     class="size-full object-cover transition duration-500 group-hover:scale-105">
                                            @else
                                                <div class="grid size-full place-items-center text-brand-600">
                                                    <x-lucide-shirt class="size-12" aria-hidden="true" />
                                                </div>
                                            @endif
                                        </div>
                                        <div class="m-1.5 flex items-center justify-between gap-1 rounded-2xl bg-white/70 px-3 py-2.5 sm:m-2">
                                            <span class="truncate text-xs font-extrabold sm:text-sm">{{ $category->name }}</span>
                                            <x-lucide-chevron-left class="size-4 shrink-0 text-ink-soft" aria-hidden="true" />
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        @if ($moreCats->isNotEmpty())
                            <ul class="mt-4 flex flex-wrap gap-2" aria-label="تصنيفات أخرى">
                                @foreach ($moreCats as $category)
                                    <li>
                                        <a href="{{ route('products.index', ['category' => $category->slug]) }}" wire:navigate
                                           class="inline-block rounded-full border border-line bg-white px-4 py-1.5 text-sm font-semibold hover:bg-surface">{{ $category->name }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </div>

            {{-- ===== الصورة المقوّسة (يسار في RTL) ===== --}}
            <div class="relative order-1 lg:order-2">
                <div class="relative aspect-4/3 overflow-hidden rounded-t-[40%_30%] rounded-b-[2.5rem] bg-brand-50 shadow-sm sm:aspect-16/10 lg:aspect-auto lg:min-h-136">
                    @forelse ($ads as $ad)
                        @if ($ad['image'])
                            <img src="{{ $ad['image'] }}" alt="{{ $ad['title'] }}"
                                 @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif
                                 class="absolute inset-0 size-full object-cover transition-opacity duration-700"
                                 :class="i === {{ $loop->index }} ? 'opacity-100' : 'opacity-0'">
                        @else
                            <div class="absolute inset-0 grid place-items-center bg-linear-to-bl from-brand-50 to-brand-100 text-brand-600 transition-opacity duration-700"
                                 :class="i === {{ $loop->index }} ? 'opacity-100' : 'opacity-0'" aria-hidden="true">
                                <x-lucide-shirt class="size-32 opacity-40" />
                            </div>
                        @endif
                    @empty
                        <div class="absolute inset-0 grid place-items-center bg-linear-to-bl from-brand-50 to-brand-100 text-brand-600" aria-hidden="true">
                            <x-lucide-shirt class="size-32 opacity-40" />
                        </div>
                    @endforelse

                    @if ($count > 1)
                        {{-- السهمان على جانبي الصورة: اليسار = التالي في الاتجاه العربي --}}
                        <button type="button" @click="next()" aria-label="الشريحة التالية"
                                class="absolute left-3 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/95 text-ink shadow hover:bg-white">
                            <x-lucide-chevron-left class="size-5" aria-hidden="true" />
                        </button>
                        <button type="button" @click="prev()" aria-label="الشريحة السابقة"
                                class="absolute right-3 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/95 text-ink shadow hover:bg-white">
                            <x-lucide-chevron-right class="size-5" aria-hidden="true" />
                        </button>
                    @endif
                </div>

                @if ($count > 1)
                    {{-- النقاط + شريط التقدم + إيقاف --}}
                    <div class="mt-4 flex items-center gap-4 px-2">
                        <div class="flex items-center gap-2">
                            @foreach ($ads as $ad)
                                <button type="button" @click="go({{ $loop->index }})"
                                        aria-label="الانتقال إلى الشريحة {{ $loop->iteration }}"
                                        :aria-current="i === {{ $loop->index }} ? 'true' : 'false'"
                                        :class="i === {{ $loop->index }} ? 'bg-brand-600' : 'bg-brand-600/25 hover:bg-brand-600/50'"
                                        class="size-3 rounded-full transition"></button>
                            @endforeach
                        </div>

                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-black/10" aria-hidden="true">
                            <div class="h-full rounded-full bg-brand-600" :style="`width: ${p}%`"></div>
                        </div>

                        <button type="button" @click="paused = !paused"
                                :aria-label="paused ? 'تشغيل التبديل التلقائي' : 'إيقاف التبديل التلقائي'"
                                class="grid size-9 place-items-center rounded-full bg-ink/70 text-white hover:bg-ink">
                            <x-lucide-pause class="size-4" x-show="!paused" aria-hidden="true" />
                            <x-lucide-play class="size-4" x-show="paused" x-cloak aria-hidden="true" />
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </section>
