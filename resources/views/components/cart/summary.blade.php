            {{-- ملخص الطلب --}}
            <aside class="space-y-4 rounded-lg border border-line bg-surface p-5 lg:sticky lg:top-24" aria-labelledby="summary-heading">
                <h2 id="summary-heading" class="text-lg font-extrabold">ملخص الطلب</h2>

                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-soft">المجموع الفرعي</dt>
                        <dd class="font-semibold">{{ \App\Support\Money::formatMinor($summary->subtotalMinor, $currency) }}</dd>
                    </div>

                    @if ($summary->taxEnabled)
                        <div class="flex justify-between gap-3">
                            <dt class="text-ink-soft">الضريبة ({{ rtrim(rtrim($summary->taxRate, '0'), '.') }}%)</dt>
                            <dd class="font-semibold">{{ \App\Support\Money::formatMinor($summary->taxMinor, $currency) }}</dd>
                        </div>
                    @endif

                    <x-cart.free-shipping-bar :summary="$summary" :currency="$currency" />

                    <div class="flex justify-between gap-3">
                        <dt class="text-ink-soft">رسوم التوصيل</dt>
                        <dd class="font-semibold">
                            {{ $summary->deliveryFeeMinor > 0 ? \App\Support\Money::formatMinor($summary->deliveryFeeMinor, $currency) : 'مجانًا' }}
                        </dd>
                    </div>

                    <div class="flex justify-between gap-3 border-t border-line pt-3 text-base">
                        <dt class="font-extrabold">الإجمالي</dt>
                        <dd class="font-extrabold text-brand-700">{{ \App\Support\Money::formatMinor($summary->totalMinor, $currency) }}</dd>
                    </div>
                </dl>

                @if ($summary->hasBlockingIssues())
                    <p class="rounded-md border border-danger/30 bg-white px-3 py-2 text-sm font-semibold text-danger" role="alert">
                        في سلتك عناصر تحتاج تعديلًا قبل إتمام الطلب.
                    </p>
                @endif

                @if ($summary->hasBlockingIssues())
                    <span class="block cursor-not-allowed rounded-md bg-ink-soft/40 px-5 py-3 text-center text-base font-extrabold text-white" aria-disabled="true">إتمام الطلب</span>
                @else
                    <a href="{{ route('checkout') }}" wire:navigate
                       class="block rounded-md bg-brand-600 px-5 py-3 text-center text-base font-extrabold text-white hover:bg-brand-700">
                        إتمام الطلب
                    </a>
                @endif
                <p class="text-xs text-ink-soft">الدفع عند الاستلام. الأسعار المعروضة محسوبة من الخادم وقد تتغير قبل تأكيد الطلب.</p>

                <a href="{{ route('products.index') }}" wire:navigate
                   class="block text-center text-sm font-semibold text-brand-700 underline underline-offset-4">
                    متابعة التسوق
                </a>
            </aside>
