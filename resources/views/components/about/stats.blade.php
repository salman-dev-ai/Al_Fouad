    {{-- ───── الأرقام: شريط طافٍ يتداخل مع الغلاف ───── --}}
    @if ($show['stats'])
        <section class="relative z-10 mx-auto max-w-6xl px-4 sm:px-6 {{ $show['hero'] ? '-mt-16 sm:-mt-20' : 'mt-10' }}" aria-label="أرقامنا">
            <dl class="grid grid-cols-2 overflow-hidden rounded-3xl bg-white shadow-xl shadow-ink/10 ring-1 ring-line {{ $statCols[min(4, count($c['stats']))] }} sm:divide-x sm:divide-x-reverse sm:divide-line max-sm:[&>div:nth-child(n+3)]:border-t max-sm:[&>div]:border-line">
                @foreach ($c['stats'] as $stat)
                    <div class="flex flex-col-reverse items-center gap-1 px-4 py-7 text-center sm:py-9" wire:key="stat-{{ $loop->index }}">
                        <dt class="text-sm font-medium text-ink-soft">{{ $stat['label'] }}</dt>
                        <dd class="text-3xl font-black tracking-tight text-brand-700 sm:text-4xl">{{ $stat['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endif
