        {{-- ========== لوحة الفلاتر ========== --}}
        <div class="mt-4 grid gap-6 rounded-2xl border border-line bg-white p-5 shadow-sm md:grid-cols-[1.4fr_1fr_1fr]"
             x-show="open.price || open.color || open.size" x-transition.opacity>

            {{-- السعر: شريط مزدوج (لا طلبات أثناء السحب، فقط عند الإفلات) + حقول رقمية --}}
            @if ($hasRange)
                <section id="panel-price" x-show="open.price" aria-labelledby="price-h"
                         x-data="{
                            min: {{ $pb['min'] }}, max: {{ $pb['max'] }}, step: {{ $step }},
                            lo: {{ $loVal }}, hi: {{ $hiVal }},
                            init() {
                                this.$wire.$watch('minPrice', v => this.lo = v === '' ? this.min : Math.max(this.min, Number(v)));
                                this.$wire.$watch('maxPrice', v => this.hi = v === '' ? this.max : Math.min(this.max, Number(v)));
                            },
                            pct(v) { return ((v - this.min) / (this.max - this.min)) * 100 },
                            fixLo() { if (this.lo > this.hi) this.lo = this.hi },
                            fixHi() { if (this.hi < this.lo) this.hi = this.lo },
                            commit() {
                                this.$wire.set('minPrice', this.lo <= this.min ? '' : String(this.lo), false);
                                this.$wire.set('maxPrice', this.hi >= this.max ? '' : String(this.hi));
                            },
                         }">
                    <h2 id="price-h" class="text-base font-extrabold">السعر ({{ $this->currency }})</h2>

                    <div class="relative mt-5 h-6" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
                        <div class="absolute inset-x-0 top-1/2 h-1 -translate-y-1/2 rounded-full bg-line" aria-hidden="true"></div>
                        <div class="absolute top-1/2 h-1 -translate-y-1/2 rounded-full bg-brand-600" aria-hidden="true"
                             :style="`inset-inline-start:${pct(lo)}%; inset-inline-end:${100 - pct(hi)}%`"></div>

                        <input type="range" class="range-dual absolute inset-0 w-full" :min="min" :max="max" :step="step"
                               x-model.number="lo" @input="fixLo()" @change="commit()" aria-label="أقل سعر"
                               :class="lo > max - step * 2 ? 'z-20' : 'z-10'">
                        <input type="range" class="range-dual absolute inset-0 w-full z-10" :min="min" :max="max" :step="step"
                               x-model.number="hi" @input="fixHi()" @change="commit()" aria-label="أعلى سعر">
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <label class="flex flex-1 items-center gap-2 text-sm text-ink-soft">
                            من
                            <input type="number" min="0" inputmode="numeric" placeholder="{{ $pb['min'] }}" wire:model.live.debounce.600ms="minPrice"
                                   class="w-full rounded-lg border border-line px-3 py-2 text-sm text-ink focus:border-brand-500">
                        </label>
                        <span aria-hidden="true">–</span>
                        <label class="flex flex-1 items-center gap-2 text-sm text-ink-soft">
                            إلى
                            <input type="number" min="0" inputmode="numeric" placeholder="{{ $pb['max'] }}" wire:model.live.debounce.600ms="maxPrice"
                                   class="w-full rounded-lg border border-line px-3 py-2 text-sm text-ink focus:border-brand-500">
                        </label>
                    </div>
                </section>
            @endif

            {{-- اللون --}}
            @if (count($facets['colors']) > 0)
                <fieldset id="panel-color" x-show="open.color" class="md:border-s md:border-line md:ps-6">
                    <legend class="mb-4 text-base font-extrabold">اللون</legend>
                    <div class="flex flex-wrap gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" wire:model.live="color" value="" class="peer sr-only">
                            <span class="block rounded-full border border-line px-3 py-1.5 text-xs font-semibold peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600">الكل</span>
                        </label>

                        @foreach ($facets['colors'] as $option)
                            <label class="cursor-pointer" title="{{ $option['name'] }}" wire:key="color-{{ $loop->index }}">
                                <input type="radio" wire:model.live="color" value="{{ $option['name'] }}" class="peer sr-only">
                                @if ($option['hex'])
                                    <span class="block size-9 rounded-full border border-line ring-offset-2 peer-checked:ring-2 peer-checked:ring-brand-600 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600"
                                          style="background-color: {{ $option['hex'] }}"></span>
                                    <span class="sr-only">{{ $option['name'] }}</span>
                                @else
                                    <span class="block rounded-full border border-line px-3 py-1.5 text-xs font-semibold peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600">{{ $option['name'] }}</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endif

            {{-- المقاس --}}
            @if (count($facets['sizes']) > 0)
                <fieldset id="panel-size" x-show="open.size" class="md:border-s md:border-line md:ps-6">
                    <legend class="mb-4 text-base font-extrabold">المقاس</legend>
                    <div class="flex flex-wrap gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" wire:model.live="size" value="" class="peer sr-only">
                            <span class="block min-w-11 rounded-lg border border-line px-3 py-2 text-center text-sm font-semibold peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600">الكل</span>
                        </label>
                        @foreach ($facets['sizes'] as $option)
                            <label class="cursor-pointer" wire:key="size-{{ $loop->index }}">
                                <input type="radio" wire:model.live="size" value="{{ $option }}" class="peer sr-only">
                                <span class="block min-w-11 rounded-lg border border-line px-3 py-2 text-center text-sm font-semibold peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600">{{ $option }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endif
        </div>
