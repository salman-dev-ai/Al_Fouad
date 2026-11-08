{{-- زر أيقونة دائري للهيدر مع تسمية تظهر عند المرور أو التركيز. --}}
{{-- الخصائص: href، icon (اسم أيقونة Lucide)، label، active. والـ slot لشارات العدّاد. --}}
@props(['href', 'icon', 'label', 'active' => false])

<div class="group relative">
    <a href="{{ $href }}" wire:navigate aria-label="{{ $label }}" @if ($active) aria-current="page" @endif
       {{ $attributes->class([
            'relative grid size-10 place-items-center rounded-full border text-white transition motion-reduce:transition-none sm:size-11',
            'focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-white',
            'border-white/50 bg-brand-500 shadow-inner' => $active,
            'border-white/25 hover:scale-105 hover:bg-white/15' => ! $active,
        ]) }}>
        <x-dynamic-component :component="'lucide-' . $icon" class="size-5 sm:size-[22px]" aria-hidden="true" />
        {{ $slot }}
    </a>

    <span role="tooltip"
          class="pointer-events-none absolute left-1/2 top-full z-50 mt-2 -translate-x-1/2 whitespace-nowrap rounded-lg bg-ink px-3 py-1.5 text-xs font-bold text-white opacity-0 shadow-lg transition motion-reduce:transition-none group-focus-within:opacity-100 group-hover:opacity-100">
        {{ $label }}
    </span>
</div>
