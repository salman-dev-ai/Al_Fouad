    {{-- ───── شركاؤنا: شعارات دائرية متحركة ───── --}}
    @if ($show['partners'])
        @php
            $reps = max(1, (int) ceil(12 / count($c['partners'])));
            $set = collect(range(1, $reps))->flatMap(fn () => $c['partners'])->all();
            $seconds = max(25, min(90, count($set) * 4));
        @endphp

        <section class="py-20 sm:py-28" aria-labelledby="partners-heading">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-ui.section-heading eyebrow="شركاؤنا" title="نعمل مع نخبة من الشركاء" :center="true" />
                <h2 id="partners-heading" class="sr-only">شركاؤنا</h2>
            </div>

            <div class="marquee mt-12 overflow-hidden motion-reduce:overflow-x-auto [-webkit-mask-image:linear-gradient(to_right,transparent,#000_10%,#000_90%,transparent)] [mask-image:linear-gradient(to_right,transparent,#000_10%,#000_90%,transparent)]">
                <div class="marquee-track flex w-max items-center gap-10 pe-10" style="animation-duration: {{ $seconds }}s">
                    @foreach ([false, true] as $isCopy)
                        {{-- النسخة الثانية للحركة المتصلة فقط: مخفية عن قارئ الشاشة وغير قابلة للتركيز. --}}
                        <ul class="flex items-center gap-10" {{ $isCopy ? 'aria-hidden="true" inert' : '' }}>
                            @foreach ($set as $partner)
                                <li>
                                    @if ($partner['url'])
                                        <a href="{{ $partner['url'] }}" target="_blank" rel="noopener noreferrer nofollow"
                                           aria-label="{{ $partner['name'] }}"
                                           class="grid size-24 place-items-center rounded-full bg-surface p-5 ring-1 ring-line transition hover:bg-white hover:ring-brand-500 sm:size-28">
                                            <img src="{{ $url($partner['logo']) }}" alt="{{ $partner['name'] }}" width="80" height="80"
                                                 loading="lazy" decoding="async" class="size-full object-contain grayscale transition hover:grayscale-0">
                                        </a>
                                    @else
                                        <span class="grid size-24 place-items-center rounded-full bg-surface p-5 ring-1 ring-line sm:size-28">
                                            <img src="{{ $url($partner['logo']) }}" alt="{{ $partner['name'] }}" width="80" height="80"
                                                 loading="lazy" decoding="async" class="size-full object-contain grayscale">
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
