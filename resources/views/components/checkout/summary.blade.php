            <aside class="space-y-4 rounded-lg border border-line bg-surface p-5 lg:sticky lg:top-24" aria-labelledby="summary-heading">
                <h2 id="summary-heading" class="text-lg font-extrabold">ملخص الطلب</h2>

                <ul class="divide-y divide-line text-sm">
                    @foreach ($summary->lines as $line)
                        <li class="flex justify-between gap-3 py-2">
                            <span class="min-w-0">
                                <span class="block font-semibold">{{ $line['variant']->product->name }}</span>
                                <span class="text-xs text-ink-soft">{{ $line['variant']->color }} / {{ $line['variant']->size }} × {{ $line['quantity'] }}</span>
                            </span>
                            <span class="whitespace-nowrap font-semibold">{{ \App\Support\Money::formatMinor($line['line_minor'], $currency) }}</span>
                        </li>
                    @endforeach
                </ul>

                <x-cart.totals :subtotal="$summary->subtotalMinor"
                                :tax="$summary->taxEnabled ? $summary->taxMinor : null" :tax-rate="$summary->taxRate"
                                :delivery="$summary->deliveryFeeMinor" :total="$summary->totalMinor" :currency="$currency" />

                @error('checkout')
                    <p class="rounded-md border border-danger/30 bg-white px-3 py-2 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                @enderror

                @if ($summary->hasBlockingIssues())
                    <p class="rounded-md border border-danger/30 bg-white px-3 py-2 text-sm font-semibold text-danger" role="alert">
                        بعض العناصر لم تعد متاحة.
                        <a href="{{ route('cart') }}" wire:navigate class="underline">راجع السلة</a>
                    </p>
                @endif

                <button type="submit" wire:loading.attr="disabled" wire:target="placeOrder"
                        @disabled($summary->hasBlockingIssues())
                        class="w-full rounded-md bg-brand-600 px-6 py-3.5 text-base font-extrabold text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-ink-soft/40">
                    <span wire:loading.remove wire:target="placeOrder">تأكيد الطلب</span>
                    <span wire:loading wire:target="placeOrder">جارٍ إرسال الطلب…</span>
                </button>

                <p class="text-xs text-ink-soft">
                    بتأكيد الطلب توافق على حفظ بياناتك لتنفيذ طلبك والتواصل معك بشأنه.
                </p>
            </aside>
