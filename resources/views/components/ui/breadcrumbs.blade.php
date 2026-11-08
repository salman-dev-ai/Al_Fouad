@props(['items'])

<nav aria-label="مسار التصفح" class="text-sm text-ink-soft">
    <ol class="flex flex-wrap items-center gap-1.5">
        @foreach ($items as $item)
            <li class="flex items-center gap-1.5">
                @unless ($loop->first)
                    <x-lucide-chevron-left class="size-4 opacity-60" aria-hidden="true" />
                @endunless

                @if (isset($item['url']) && ! $loop->last)
                    <a href="{{ $item['url'] }}" wire:navigate class="hover:text-brand-700 hover:underline">{{ $item['label'] }}</a>
                @else
                    <span @if ($loop->last) aria-current="page" @endif class="font-semibold text-ink">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
 