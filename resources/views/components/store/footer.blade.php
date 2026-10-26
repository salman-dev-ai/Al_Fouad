<footer class="mt-16 bg-ink text-white">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-3 lg:px-8">

        {{-- الشعار والتواصل --}}
        <div class="space-y-5">
            <img src="{{ asset('images/logo-white.png') }}" alt="{{ config('app.name') }}"
                 width="114" height="48" class="h-12 w-auto" loading="lazy">

            <ul class="space-y-3 text-sm text-white/85">
                @if ($telUrl())
                    <li>
                        <a href="{{ $telUrl() }}" class="flex items-center gap-2 hover:text-white">
                            <x-lucide-phone class="size-4 shrink-0" aria-hidden="true" />
                            <span dir="ltr">{{ $settings->phone }}</span>
                        </a>
                    </li>
                @endif

                @if ($whatsappUrl())
                    <li>
                        <a href="{{ $whatsappUrl() }}" target="_blank" rel="noopener noreferrer"
                           class="flex items-center gap-2 hover:text-white">
                            <x-lucide-message-circle class="size-4 shrink-0" aria-hidden="true" />
                            <span>تواصل عبر واتساب</span>
                        </a>
                    </li>
                @endif

                @if (filled($settings?->email))
                    <li>
                        <a href="mailto:{{ $settings->email }}" class="flex items-center gap-2 hover:text-white">
                            <x-lucide-mail class="size-4 shrink-0" aria-hidden="true" />
                            <span dir="ltr">{{ $settings->email }}</span>
                        </a>
                    </li>
                @endif

                @if (filled($settings?->address))
                    <li class="flex items-start gap-2">
                        <x-lucide-map-pin class="mt-1 size-4 shrink-0" aria-hidden="true" />
                        <span>
                            {{ $settings->address }}
                            @if (filled($settings->map_url))
                                <a href="{{ $settings->map_url }}" target="_blank" rel="noopener noreferrer"
                                   class="block font-semibold underline underline-offset-4 hover:text-white">
                                    عرض على الخريطة
                                </a>
                            @endif
                        </span>
                    </li>
                @endif
            </ul>
        </div>

        {{-- أوقات العمل --}}
        @if ($hours())
            <div>
                <h2 class="mb-4 flex items-center gap-2 text-base font-bold">
                    <x-lucide-clock class="size-4" aria-hidden="true" />
                    أوقات العمل
                </h2>
                <dl class="space-y-1.5 text-sm text-white/85">
                    @foreach ($hours() as $row)
                        <div class="flex justify-between gap-4">
                            <dt>{{ $row['day'] }}</dt>
                            <dd dir="ltr">{{ $row['text'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endif

        {{-- روابط التواصل الاجتماعي --}}
        @if ($socialLinks())
            <div>
                <h2 class="mb-4 text-base font-bold">تابعنا</h2>
                <ul class="flex flex-wrap gap-2 text-sm">
                    @foreach ($socialLinks() as $label => $url)
                        <li>
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                               class="inline-block rounded-md border border-white/25 px-3 py-1.5 text-white/90 hover:border-white hover:text-white">
                                {{ $label }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <div class="border-t border-white/15 py-4 text-center text-xs text-white/70">
        © {{ date('Y') }} {{ config('app.name') }}. جميع الحقوق محفوظة.
    </div>
</footer>
