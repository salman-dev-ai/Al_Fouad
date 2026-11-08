            <ul wire:loading.class="opacity-60" class="divide-y divide-line rounded-lg border border-line transition-opacity">
                @foreach ($summary->lines as $line)
                    @php
                        $item = $line['item'];
                        $variant = $line['variant'];
                        $product = $variant->product;
                        $image = $product->imageForColor($variant->product_color_id);                        $canIncrease = $line['quantity'] < min($variant->stock_quantity, $max);
                    @endphp

                    <li wire:key="line-{{ $item->id }}" class="flex gap-4 p-4">
                        <a href="{{ route('products.show', ['product' => $product->slug]) }}" wire:navigate
                           class="block w-20 shrink-0 sm:w-24">
                            <span class="block aspect-[3/4] overflow-hidden rounded-md bg-brand-50">
                                @if ($image)
                                    <img src="{{ $image->url }}" alt="{{ $image->alt_text ?: $product->name }}"
                                         loading="lazy" decoding="async" class="size-full object-cover">
                                @else
                                    <span class="flex size-full items-center justify-center text-brand-500/50" aria-hidden="true">
                                        <x-lucide-shirt class="size-8" />
                                    </span>
                                @endif
                            </span>
                        </a>

                        <div class="flex min-w-0 flex-1 flex-col gap-2">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ route('products.show', ['product' => $product->slug]) }}" wire:navigate
                                       class="line-clamp-2 font-bold hover:text-brand-700">{{ $product->name }}</a>
                                    <p class="text-sm text-ink-soft">{{ $variant->color }} / {{ $variant->size }}</p>
                                    <p class="text-xs text-ink-soft">سعر القطعة: {{ \App\Support\Money::formatMinor($line['unit_minor'], $currency) }}</p>
                                </div>
                                <p class="whitespace-nowrap font-extrabold text-brand-700">
                                    {{ \App\Support\Money::formatMinor($line['line_minor'], $currency) }}
                                </p>
                            </div>

                            @if ($line['issue'])
                                <p class="text-sm font-semibold text-danger" role="alert">{{ $line['issue'] }}</p>
                            @endif

                            @error("item.{$item->id}")
                                <p class="text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                            @enderror

                            <div class="mt-auto flex items-center justify-between gap-3">
                                <div class="inline-flex items-center rounded-md border border-line" role="group"
                                     aria-label="كمية {{ $product->name }}">
                                    <button type="button" wire:click="changeQuantity({{ $item->id }}, -1)"
                                            @disabled($line['quantity'] <= 1) aria-label="إنقاص الكمية"
                                            class="size-9 disabled:opacity-40">
                                        <x-lucide-minus class="mx-auto size-4" aria-hidden="true" />
                                    </button>
                                    <output class="min-w-9 text-center text-sm font-bold">{{ $line['quantity'] }}</output>
                                    <button type="button" wire:click="changeQuantity({{ $item->id }}, 1)"
                                            @disabled(! $canIncrease) aria-label="زيادة الكمية"
                                            class="size-9 disabled:opacity-40">
                                        <x-lucide-plus class="mx-auto size-4" aria-hidden="true" />
                                    </button>
                                </div>

                                <button type="button" wire:click="remove({{ $item->id }})"
                                        class="flex items-center gap-1 text-sm font-semibold text-ink-soft hover:text-danger">
                                    <x-lucide-trash-2 class="size-4" aria-hidden="true" />
                                    حذف
                                </button>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
