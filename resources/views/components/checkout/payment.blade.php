                @auth
                    @if ($addressChoice === '')
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="saveAddress" class="size-4 rounded border-line accent-brand-600">
                            احفظ هذا العنوان في حسابي لأستخدمه في طلباتي القادمة
                        </label>
                    @endif
                @endauth

                <section class="space-y-3 rounded-lg border border-line p-5" aria-labelledby="payment-heading">
                    <h2 id="payment-heading" class="text-lg font-extrabold">طريقة الدفع</h2>
                    <div class="flex items-center gap-3 rounded-md border-2 border-brand-600 bg-brand-50 p-4">
                        <x-lucide-banknote class="size-6 shrink-0 text-brand-700" aria-hidden="true" />
                        <div>
                            <p class="font-bold">الدفع عند الاستلام</p>
                            <p class="text-sm text-ink-soft">ادفع نقدًا عند استلام طلبك.</p>
                        </div>
                    </div>
                </section>
