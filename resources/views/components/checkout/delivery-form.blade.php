                <section class="space-y-4 rounded-lg border border-line p-5" aria-labelledby="delivery-heading">
                    <h2 id="delivery-heading" class="text-lg font-extrabold">بيانات الاستلام</h2>
                    @if ($this->addresses->isNotEmpty())
                        <p class="text-sm text-ink-soft">يمكنك تعديل أي بيانات لهذا الطلب دون تغيير عنوانك المحفوظ.</p>
                    @endif

                    <x-ui.field name="form.recipient_name" label="الاسم الكامل" required autocomplete="name"
                                   wire:model.blur="form.recipient_name" />

                    <x-ui.field name="form.phone" label="رقم الجوال" required type="tel" inputmode="tel" dir="ltr"
                                   placeholder="777123456" autocomplete="tel"
                                   helper="سنتواصل معك على هذا الرقم لتأكيد الطلب." wire:model.blur="form.phone" />

                    <x-ui.field name="email" label="البريد الإلكتروني" type="email" dir="ltr"
                                   autocomplete="email" wire:model.blur="email" />

                    <x-ui.field name="form.city" label="المدينة" required autocomplete="address-level2"
                                   wire:model.blur="form.city" />

                    <x-ui.field name="form.address_line" as="textarea" label="العنوان بالتفصيل" required
                                   placeholder="الحي، الشارع، أقرب معلم..." autocomplete="street-address"
                                   wire:model.blur="form.address_line" />

                    <x-ui.field name="form.notes" as="textarea" label="ملاحظات للتوصيل" wire:model.blur="form.notes" />
                </section>
