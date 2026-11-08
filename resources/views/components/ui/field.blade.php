@props(['name', 'label', 'required' => false, 'helper' => null, 'as' => 'input', 'type' => 'text'])

@php
    $id = 'f-' . $name;
    $describedBy = trim(($helper ? "{$id}-help " : '') . ($errors->has($name) ? "{$id}-error" : ''));
    $classes = 'w-full rounded-md border bg-white px-3 py-2.5 text-sm focus:border-brand-500 '
        . ($errors->has($name) ? 'border-danger' : 'border-line');
@endphp

<div>
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-bold">
        {{ $label }}
        @unless ($required)
            <span class="font-normal text-ink-soft">(اختياري)</span>
        @endunless
    </label>

    @if ($as === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="3"
                  {{ $attributes->merge(['class' => $classes]) }}
                  @if ($required) required @endif
                  aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
                  @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif></textarea>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
               {{ $attributes->merge(['class' => $classes]) }}
               @if ($required) required @endif
               aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
               @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
    @endif

    @if ($helper)
        <p id="{{ $id }}-help" class="mt-1 text-xs text-ink-soft">{{ $helper }}</p>
    @endif

    @error($name)
        <p id="{{ $id }}-error" class="mt-1 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
    @enderror
</div>
