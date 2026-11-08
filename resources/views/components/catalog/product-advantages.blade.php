        {{-- ========== عمود المزايا (من إعدادات المتجر فقط) ========== --}}
        <ul class="mt-6 grid grid-cols-2 gap-3 xl:mt-0 xl:grid-cols-1 xl:content-start" aria-label="مزايا الشراء">
            <li class="rounded-2xl border border-line bg-white p-3 text-center">
                <span class="mx-auto grid size-11 place-items-center rounded-full bg-brand-50 text-brand-700"><x-lucide-truck class="size-5" aria-hidden="true" /></span>
                <p class="mt-2 text-sm font-extrabold">توصيل إلى بابك</p>
                <p class="text-xs text-ink-soft">نوصل طلبك أينما كنت</p>
            </li>
            <li class="rounded-2xl border border-line bg-white p-3 text-center">
                <span class="mx-auto grid size-11 place-items-center rounded-full bg-brand-50 text-brand-700"><x-lucide-banknote class="size-5" aria-hidden="true" /></span>
                <p class="mt-2 text-sm font-extrabold">الدفع عند الاستلام</p>
                <p class="text-xs text-ink-soft">ادفع بعد أن تستلم</p>
            </li>
            @if ($this->returnDays)
                <li class="rounded-2xl border border-line bg-white p-3 text-center">
                    <span class="mx-auto grid size-11 place-items-center rounded-full bg-brand-50 text-brand-700"><x-lucide-rotate-ccw class="size-5" aria-hidden="true" /></span>
                    <p class="mt-2 text-sm font-extrabold">
                        @if ($this->returnUrl)
                            <a href="{{ $this->returnUrl }}" class="hover:underline">استرجاع سهل</a>
                        @else
                            استرجاع سهل
                        @endif
                    </p>
                    <p class="text-xs text-ink-soft">خلال {{ $this->returnDays }} أيام</p>
                </li>
            @endif
            @if ($this->whatsappUrl)
                <li class="rounded-2xl border border-line bg-white p-3 text-center">
                    <span class="mx-auto grid size-11 place-items-center rounded-full bg-brand-50 text-brand-700"><x-lucide-headset class="size-5" aria-hidden="true" /></span>
                    <p class="mt-2 text-sm font-extrabold"><a href="{{ $this->whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="hover:underline">دعم العملاء</a></p>
                    <p class="text-xs text-ink-soft">عبر واتساب</p>
                </li>
            @endif
        </ul>
