        {{-- ========== شريط التصنيفات ========== --}}
        @if (count($facets['categories']) > 0)
            <nav class="mt-6 flex flex-col gap-4 rounded-2xl bg-brand-50/70 p-4 lg:flex-row lg:items-center" aria-label="تصفح حسب التصنيف">
                <h2 class="shrink-0 text-base font-extrabold lg:px-2">تصفح حسب التصنيف</h2>

                <ul class="flex gap-3 overflow-x-auto pb-1 [scrollbar-width:none] lg:flex-wrap lg:overflow-visible lg:pb-0">
                    <li class="shrink-0">
                        <button type="button" wire:click="selectCategory('')" aria-pressed="{{ $category === '' ? 'true' : 'false' }}"
                                @class([
                                    'flex items-center gap-3 rounded-xl border p-1.5 pe-5 text-sm font-bold transition',
                                    'border-brand-600 bg-brand-600 text-white shadow-sm' => $category === '',
                                    'border-line bg-white hover:bg-surface' => $category !== '',
                                ])>
                            <span class="grid size-10 place-items-center rounded-lg bg-white/20">
                                <x-lucide-layout-grid class="size-5" aria-hidden="true" />
                            </span>
                            الكل
                        </button>
                    </li>

                    @foreach ($facets['categories'] as $item)
                        <li class="shrink-0" wire:key="cat-{{ $item['slug'] }}">
                            <button type="button" wire:click="$set('category', @js($item['slug']))"
                                    aria-pressed="{{ $category === $item['slug'] ? 'true' : 'false' }}"
                                    @class([
                                        'flex items-center gap-3 rounded-xl border p-1.5 pe-5 text-sm font-bold transition',
                                        'border-brand-600 bg-brand-600 text-white shadow-sm' => $category === $item['slug'],
                                        'border-line bg-white hover:bg-surface' => $category !== $item['slug'],
                                    ])>
                                @if ($item['image'])
                                    <img src="{{ asset('storage/' . $item['image']) }}" alt="" width="40" height="40" loading="lazy" decoding="async"
                                         class="size-10 rounded-lg object-cover">
                                @else
                                    <span class="grid size-10 place-items-center rounded-lg bg-brand-50 text-brand-600">
                                        <x-lucide-shirt class="size-5" aria-hidden="true" />
                                    </span>
                                @endif
                                {{ $item['name'] }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif
