    {{-- ========== دعوة للتواصل ========== --}}
    @if ($whatsappUrl)
        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8" aria-labelledby="help-heading">
            <div class="flex flex-col items-center justify-between gap-5 rounded-3xl bg-brand-700 p-8 text-center text-white sm:flex-row sm:text-start">
                <div>
                    <h2 id="help-heading" class="text-xl font-extrabold sm:text-2xl">تحتاج مساعدة في اختيار المقاس؟</h2>
                    <p class="mt-1 text-white/90">راسلنا على واتساب وسنساعدك في اختيار الأنسب.</p>
                </div>
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                   class="inline-flex shrink-0 items-center gap-2 rounded-full bg-white px-6 py-3 font-extrabold text-brand-700 hover:bg-brand-50">
                    <x-lucide-message-circle class="size-5" aria-hidden="true" />
                    تواصل معنا
                </a>
            </div>
        </section>
    @endif
